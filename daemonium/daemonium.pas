program daemonium;

{$mode objfpc}{$H+}

uses
  Auxilia,
  Database,
  ClavesLlm,
  Usores,
  Fabulatio,
  Scientia,
  classes,
  sysutils,
  sockets,
  strutils
  {$IFDEF UNIX}
  , baseunix
  {$ELSE}
  , winsock2
  {$ENDIF}
  ;

const
  PORTUS = 8080;

procedure SocketClose(Sock: longint);
begin
  {$IFDEF UNIX}
  FpClose(Sock);
  {$ELSE}
  CloseSocket(Sock);
  {$ENDIF}
end;

procedure TractareClientem(CliensSock: longint);
var
  LineaData: String;
  Mandatum, Parametrum1, Parametrum2: String;
  Responsum: String;
  DataInput: TStringList;
  Buffer: array[0..1023] of char;
  BytesRead: Integer;
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
  until (BytesRead <= 0) or (Pos(sLineBreak, LineaData) > 0) or (Pos(#10, LineaData) > 0);

  LineaData := Trim(LineaData);
  if LineaData = '' then
  begin
    SocketClose(CliensSock);
    Exit;
  end;

  DataInput := TStringList.Create;
  try
    DataInput.StrictDelimiter := True;
    DataInput.Delimiter := '|';
    DataInput.DelimitedText := LineaData;
    Mandatum := '';
    Parametrum1 := '';
    Parametrum2 := '';

    if DataInput.Count > 0 then Mandatum := DataInput[0];
    if DataInput.Count > 1 then Parametrum1 := DataInput[1];
    if DataInput.Count > 2 then Parametrum2 := DataInput[2];

    WriteLn('[REQ] Cmd: ', Mandatum, ' | Param1: ', Parametrum1, ' | Params: ', DataInput.Count);
    Flush(StdOut);

    if Mandatum = 'CREARE_USOREM' then
      Responsum := CreareUsorem(Parametrum1, Parametrum2)
    else if Mandatum = 'CREARE_USOREM_PLENUM' then
    begin
       if DataInput.Count > 4 then
         Responsum := CreareUsoremPlenum(Parametrum1, Parametrum2, DataInput[3], DataInput[4]);
    end
    else if Mandatum = 'INTRARE' then
      Responsum := Intrare(Parametrum1, Parametrum2)
    else if Mandatum = 'INTRARE_PLENUM' then
    begin
       if DataInput.Count > 3 then
         Responsum := IntrarePlenum(Parametrum1, Parametrum2, DataInput[3]);
    end
    else if Mandatum = 'PETERE_RECUPERATIONEM' then
      Responsum := PetereRecuperationem(Parametrum1)
    else if Mandatum = 'ADDERE_NUNTIUM' then
    begin
      if DataInput.Count > 4 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[3]) then
          Responsum := AddereNuntium(Parametrum1, Parametrum2, DataInput[4])
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'LEGENDE_NUNTIOS' then
    begin
      if DataInput.Count > 3 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[3]) then
          Responsum := LegendeNuntios(Parametrum1, Parametrum2)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'INVESTIGARE' then
      Responsum := Investigare(Parametrum1)
    else if Mandatum = 'SALVARE_SCIENTIAM' then
      Responsum := SalvareScientiam(Parametrum1)
    else if Mandatum = 'INDEX_FABULATIONUM' then
    begin
      if DataInput.Count > 2 then
      begin
        if VerificareFingerprint(Parametrum1, Parametrum2) then
          Responsum := IndexFabulationum(Parametrum1)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'DELE_FABULATIONEM' then
    begin
      if DataInput.Count > 3 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[3]) then
          Responsum := DeleFabulationem(Parametrum1, Parametrum2)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'RENOMINARE_FABULATIONEM' then
    begin
      if DataInput.Count > 4 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[4]) then
          Responsum := RenominareFabulationem(Parametrum1, Parametrum2, DataInput[3])
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'RENOMINARE_USOREM' then
    begin
      if DataInput.Count > 3 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[3]) then
          Responsum := RenominareUsorem(Parametrum1, Parametrum2)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'MUTARE_TESSARAM' then
    begin
      if DataInput.Count > 4 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[4]) then
          Responsum := MutareTessellam(Parametrum1, Parametrum2, DataInput[3])
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'DELERE_RATIONEM' then
    begin
      if DataInput.Count > 2 then
      begin
        if VerificareFingerprint(Parametrum1, Parametrum2) then
          Responsum := DelereRationem(Parametrum1)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'DELERE_OMNES_FABULATIONES' then
    begin
      if DataInput.Count > 2 then
      begin
        if VerificareFingerprint(Parametrum1, Parametrum2) then
          Responsum := DelereOmnesFabulationes(Parametrum1)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'NUMERARE_NUNTIOS' then
    begin
      if DataInput.Count > 2 then
      begin
        if VerificareFingerprint(Parametrum1, Parametrum2) then
          Responsum := NumerareNuntiosUsoris(Parametrum1)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'SERVARE_OPTIONES' then
    begin
      if DataInput.Count > 3 then
      begin
        if VerificareFingerprint(Parametrum1, DataInput[3]) then
          Responsum := ServareOptiones(Parametrum1, Parametrum2)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'LEGERE_OPTIONES' then
    begin
      if DataInput.Count > 2 then
      begin
        if VerificareFingerprint(Parametrum1, Parametrum2) then
          Responsum := LegereOptiones(Parametrum1)
        else
          Responsum := FormareResponsum(403, 'Error', 'FP mismatch');
      end;
    end
    else if Mandatum = 'STATUM_CLAVIS_LLM' then
    begin
      if DataInput.Count > 2 then
        Responsum := StatumClavisLLM(Parametrum1, Parametrum2);
    end
    else if Mandatum = 'NOTARE_EVENTUM_CLAVIS_LLM' then
    begin
      if DataInput.Count > 6 then
      begin
        if DataInput.Count > 7 then
          Responsum := NotareEventumClavisLLM(Parametrum1, Parametrum2, DataInput[3], DataInput[4], DataInput[5], DataInput[6], DataInput[7])
        else
          Responsum := NotareEventumClavisLLM(Parametrum1, Parametrum2, DataInput[3], DataInput[4], DataInput[5], DataInput[6], '');
      end;
    end
    else if Mandatum = 'SYNC_CLAVES_LLM' then
    begin
      if Parametrum1 <> '' then
        Responsum := SynchronizareClavesLLMProvider(Parametrum1, True)
      else
      begin
        SynchronizareOmnesClavesLLM;
        Responsum := FormareResponsum(200, 'Successus', 'Omnes claves LLM synchronizatae sunt');
      end;
    end
    else
      Responsum := FormareResponsum(400, 'Error', 'Mandatum incognitum');

    WriteLn('[RESP] ', Trim(Responsum));
    Flush(StdOut);
    fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
  finally
    DataInput.Free;
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
  WriteLn(' [!] ДЕМОН ПРОБУДИЛСЯ / DAEMON AWAKENED (Process #', {$IFDEF UNIX}fpgetpid{$ELSE}GetProcessID{$ENDIF}, ')');
  WriteLn(' [!] ВНИМАНИЕ: Если ты это читаешь, значит ты полез в логи докера. Зачем?');
  WriteLn(' [!] WARNING: 640KB RAM IS ENOUGH FOR EVERYONE (Currently using: ', GetFPCHeapStatus.CurrHeapUsed div 1024, 'KB)');
  WriteLn(' [!] ГОД ОТ РОЖДЕСТВА ХРИСТОВА / YEAR: ', FormatDateTime('yyyy', Now));
  WriteLn('------------------------------------------------');

  // Connect to PostgreSQL and verify schema
  InitDatabase;

  // Synchronize keys to break circular dependencies
  SynchronizareOmnesClavesLLM;

  WriteLn('Daemonium audit in portu / Listening on port ', PORTUS, '...');

  ServusSock := fpSocket(AF_INET, SOCK_STREAM, 0);
  if ServusSock = -1 then
  begin
    WriteLn('Error: Non potest creare socketum.');
    Halt(1);
  end;

  OptVal := 1;
  fpSetsockopt(ServusSock, SOL_SOCKET, SO_REUSEADDR, @OptVal, SizeOf(OptVal));

  Adres.sin_family := AF_INET;
  Adres.sin_port := htons(PORTUS);
  Adres.sin_addr.s_addr := htonl(INADDR_ANY);

  if fpBind(ServusSock, @Adres, SizeOf(Adres)) = -1 then
  begin
    WriteLn('Error: Non potest ligare socketum (bind).');
    Halt(1);
  end;

  if fpListen(ServusSock, 128) = -1 then
  begin
    WriteLn('Error: Non potest audire (listen).');
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
