program mechanica;

{$mode objfpc}{$H+}

uses
  classes,
  sysutils,
  sockets,
  process,
  strutils
  {$IFDEF UNIX}
  , baseunix
  {$ELSE}
  , winsock2
  {$ENDIF}
  ;

const
  PORTUS = 8082;

type
  TStringArray = array[0..24] of String;

const
  Forbidden: TStringArray = (
    'assign', 'reset', 'rewrite', 'append', 'close', 'erase', 'rename',
    'assignfile', 'closefile', 'windows', 'dos', 'unix', 'libc', 'sockets',
    'fpconnect', 'fpsocket', 'winapi', 'shellexecute', 'createprocess',
    'exec', 'system', 'registry', 'baseunix', 'shell', 'executeprocess'
  );

procedure SocketClose(Sock: longint);
begin
  {$IFDEF UNIX}
  FpClose(Sock);
  {$ELSE}
  CloseSocket(Sock);
  {$ENDIF}
end;

function VerifySafety(const Code: String; out ErrorMsg: String): Boolean;
var
  LowerCode: String;
  K: String;
begin
  LowerCode := LowerCase(Code);
  Result := True;
  for K in Forbidden do
  begin
    if Pos(K, LowerCode) > 0 then
    begin
      ErrorMsg := 'Security Violation: Forbidden keyword "' + K + '" detected.';
      Exit(False);
    end;
  end;
end;

function CompileCode(const Id: String; out ErrorMsg: String): Boolean;
var
  Proc: TProcess;
  OutputList: TStringList;
  TempFileName: String;
  ExeFileName: String;
begin
  Result := False;
  TempFileName := 'sandbox/temp_' + Id + '.pas';
  ExeFileName := 'sandbox/temp_' + Id;
  {$IFDEF MSWINDOWS}
  ExeFileName := ExeFileName + '.exe';
  {$ENDIF}

  Proc := TProcess.Create(nil);
  OutputList := TStringList.Create;
  try
    Proc.Executable := 'fpc';
    Proc.Parameters.Add('-O2');
    Proc.Parameters.Add(TempFileName);
    Proc.Options := Proc.Options + [poWaitOnExit, poUsePipes, poNoConsole, poStderrToOutput];
    Proc.Execute;
    OutputList.LoadFromStream(Proc.Output);
    
    if FileExists(ExeFileName) then
    begin
      Result := True;
    end
    else
    begin
      ErrorMsg := 'Compilation Failed:' + sLineBreak + OutputList.Text;
    end;
  finally
    Proc.Free;
    OutputList.Free;
  end;
end;

procedure CleanupSandbox(const Id: String);
begin
  DeleteFile('sandbox/temp_' + Id + '.pas');
  DeleteFile('sandbox/temp_' + Id + '.o');
  DeleteFile('sandbox/temp_' + Id + '.ppu');
  DeleteFile('sandbox/temp_' + Id);
  {$IFDEF MSWINDOWS}
  DeleteFile('sandbox/temp_' + Id + '.exe');
  {$ENDIF}
end;

procedure RunBinary(const Id: String; CliensSock: longint; IsStreaming: Boolean);
var
  Proc: TProcess;
  ExeFileName: String;
  Buffer: array[0..511] of char;
  BytesAvailable: LongInt;
  BytesRead: LongInt;
  StartTick: Int64;
  TimeoutMs: Int64;
  OutputChunk: String;
begin
  ExeFileName := 'sandbox/temp_' + Id;
  {$IFDEF MSWINDOWS}
  ExeFileName := ExeFileName + '.exe';
  {$ENDIF}

  if IsStreaming then
    TimeoutMs := 8000  // Simulations up to 8 seconds
  else
    TimeoutMs := 3000; // Discrete math up to 3 seconds

  Proc := TProcess.Create(nil);
  try
    Proc.Executable := ExeFileName;
    Proc.Options := [poUsePipes, poNoConsole];
    Proc.Execute;
    
    StartTick := GetTickCount64;
    
    while Proc.Running do
    begin
      if GetTickCount64 - StartTick > TimeoutMs then
      begin
        Proc.Terminate(1);
        OutputChunk := sLineBreak + 'Error: Execution Timeout Exceeded (Infinite loop?).' + sLineBreak;
        fpSend(CliensSock, PChar(OutputChunk), Length(OutputChunk), 0);
        Exit;
      end;

      BytesAvailable := Proc.Output.NumBytesAvailable;
      if BytesAvailable > 0 then
      begin
        if BytesAvailable > SizeOf(Buffer) then
          BytesAvailable := SizeOf(Buffer);
        BytesRead := Proc.Output.Read(Buffer[0], BytesAvailable);
        if BytesRead > 0 then
        begin
          fpSend(CliensSock, @Buffer[0], BytesRead, 0);
        end;
      end;
      
      Sleep(10);
    end;
    
    // Read remaining
    BytesAvailable := Proc.Output.NumBytesAvailable;
    while BytesAvailable > 0 do
    begin
      if BytesAvailable > SizeOf(Buffer) then
        BytesAvailable := SizeOf(Buffer);
      BytesRead := Proc.Output.Read(Buffer[0], BytesAvailable);
      if BytesRead > 0 then
      begin
        fpSend(CliensSock, @Buffer[0], BytesRead, 0);
      end;
      BytesAvailable := Proc.Output.NumBytesAvailable;
    end;
    
  finally
    Proc.Free;
  end;
end;

procedure TractareClientem(CliensSock: longint);
var
  LineaData: String;
  Buffer: array[0..1023] of char;
  BytesRead: Integer;
  Mandatum, Id, PascalCode: String;
  Parts: TStringList;
  TempFile: TStringList;
  ErrorMsg: String;
  Responsum: String;
begin
  LineaData := '';
  repeat
    FillChar(Buffer, SizeOf(Buffer), 0);
    BytesRead := fpRecv(CliensSock, @Buffer[0], SizeOf(Buffer) - 1, 0);
    if BytesRead > 0 then
    begin
      Buffer[BytesRead] := #0;
      LineaData := LineaData + StrPas(Buffer);
    end;
  until (BytesRead <= 0);

  LineaData := Trim(LineaData);
  if LineaData = '' then
  begin
    SocketClose(CliensSock);
    Exit;
  end;

  Parts := TStringList.Create;
  TempFile := TStringList.Create;
  try
    Parts.StrictDelimiter := True;
    Parts.Delimiter := '|';
    Parts.DelimitedText := LineaData;

    if Parts.Count < 3 then
    begin
      Responsum := '500|Error|Invalid request parts' + sLineBreak;
      fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
      Exit;
    end;

    Mandatum := Parts[0];
    Id := Parts[1];
    PascalCode := Parts[2];

    WriteLn('[MECHANICA REQ] Cmd: ', Mandatum, ' | Id: ', Id);
    Flush(StdOut);

    if not VerifySafety(PascalCode, ErrorMsg) then
    begin
      Responsum := '500|Security|' + ErrorMsg + sLineBreak;
      fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
      Exit;
    end;

    // Write file to sandbox
    TempFile.Text := PascalCode;
    TempFile.SaveToFile('sandbox/temp_' + Id + '.pas');

    if CompileCode(Id, ErrorMsg) then
    begin
      // Send 200 header first
      Responsum := '200|Success|';
      fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
      
      // Run the binary and stream its stdout straight to socket
      RunBinary(Id, CliensSock, Mandatum = 'RUN_SIMULATION');
    end
    else
    begin
      Responsum := '500|Compilation|' + ErrorMsg + sLineBreak;
      fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
    end;

    // Cleanup files
    CleanupSandbox(Id);

  finally
    Parts.Free;
    TempFile.Free;
    SocketClose(CliensSock);
  end;
end;

var
  ServusSock: longint;
  CliensSock: longint;
  Adres: TInetSockAddr;
  Len: longint;
  OptVal: longint;
begin
  WriteLn('------------------------------------------------');
  WriteLn(' [!] MECHANICA DAEMON ACTIVATED (Port ', PORTUS, ')');
  WriteLn(' [!] High performance Pascal Sandboxed runner.');
  WriteLn('------------------------------------------------');
  Flush(StdOut);

  // Ensure sandbox directory exists
  ForceDirectories('sandbox');

  ServusSock := fpSocket(AF_INET, SOCK_STREAM, 0);
  if ServusSock = -1 then
  begin
    WriteLn('Error: Cannot create socket.');
    Halt(1);
  end;

  OptVal := 1;
  fpSetsockopt(ServusSock, SOL_SOCKET, SO_REUSEADDR, @OptVal, SizeOf(OptVal));

  Adres.sin_family := AF_INET;
  Adres.sin_port := htons(PORTUS);
  Adres.sin_addr.s_addr := htonl(INADDR_ANY);

  if fpBind(ServusSock, @Adres, SizeOf(Adres)) = -1 then
  begin
    WriteLn('Error: Cannot bind socket.');
    Halt(1);
  end;

  if fpListen(ServusSock, 128) = -1 then
  begin
    WriteLn('Error: Cannot listen.');
    Halt(1);
  end;

  while True do
  begin
    Len := SizeOf(Adres);
    CliensSock := fpAccept(ServusSock, @Adres, @Len);
    if CliensSock <> -1 then
    begin
      TractareClientem(CliensSock);
    end;
  end;

  SocketClose(ServusSock);
end.
