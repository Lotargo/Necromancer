program Daemonium;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils, sockets, strutils, baseunix;

const
  PORTUS = 8080;
  TABULARIUM_USORES = 'tabularium/usores.txt';
  TABULARIUM_SCIENTIA = 'tabularium/scientia/scientia.txt';
  PREFIXUS_FABULATIO = 'tabularium/fabulatio_';

type
  TResponsum = record
    Codex: Integer;
    Nuntius: String;
    Data: String;
  end;

function FormareResponsum(Codex: Integer; Nuntius, Data: String): String;
begin
  Result := IntToStr(Codex) + '|' + Nuntius + '|' + Data + sLineBreak;
end;

function CreareUsorem(Nomen: String): String;
var
  F: TextFile;
  Linea: String;
  Invenitur: Boolean;
begin
  Invenitur := False;
  AssignFile(F, TABULARIUM_USORES);
  if FileExists(TABULARIUM_USORES) then
  begin
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      if Linea = Nomen then
      begin
        Invenitur := True;
        Break;
      end;
    end;
    CloseFile(F);
  end;

  if Invenitur then
    Result := FormareResponsum(400, 'Error', 'Usor iam exstat')
  else
  begin
    AssignFile(F, TABULARIUM_USORES);
    if not FileExists(TABULARIUM_USORES) then
      Rewrite(F)
    else
      Append(F);
    WriteLn(F, Nomen);
    CloseFile(F);
    Result := FormareResponsum(200, 'Successus', 'Usor creatus est');
  end;
end;

function Intrare(Nomen: String): String;
var
  F: TextFile;
  Linea: String;
  Invenitur: Boolean;
begin
  Invenitur := False;
  if FileExists(TABULARIUM_USORES) then
  begin
    AssignFile(F, TABULARIUM_USORES);
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      if Linea = Nomen then
      begin
        Invenitur := True;
        Break;
      end;
    end;
    CloseFile(F);
  end;

  if Invenitur then
    Result := FormareResponsum(200, 'Successus', 'Introitus permissus')
  else
    Result := FormareResponsum(404, 'Error', 'Usor non inventus');
end;

function AddereNuntium(Nomen, Nuntius: String): String;
var
  F: TextFile;
  NomenFasciculi: String;
begin
  NomenFasciculi := PREFIXUS_FABULATIO + Nomen + '.txt';
  AssignFile(F, NomenFasciculi);
  if FileExists(NomenFasciculi) then
    Append(F)
  else
    Rewrite(F);
  WriteLn(F, Nuntius);
  CloseFile(F);
  Result := FormareResponsum(200, 'Successus', 'Nuntius additus est');
end;

function LegendeNuntios(Nomen: String): String;
var
  F: TextFile;
  NomenFasciculi: String;
  Linea: String;
  Historia: String;
begin
  Historia := '';
  NomenFasciculi := PREFIXUS_FABULATIO + Nomen + '.txt';
  if FileExists(NomenFasciculi) then
  begin
    AssignFile(F, NomenFasciculi);
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      if Historia <> '' then
        Historia := Historia + '\n';
      Historia := Historia + Linea;
    end;
    CloseFile(F);
    Result := FormareResponsum(200, 'Successus', Historia);
  end
  else
    Result := FormareResponsum(404, 'Error', 'Historia non inventa');
end;

{ RAG: Investigare in Scientia }
function Investigare(VerbaQuery: String): String;
var
  F: TextFile;
  Linea: String;
  Verba: TStringList;
  I: Integer;
  MaxAestimatio: Integer;
  Aestimatio: Integer;
  MeliorLinea: String;
begin
  MeliorLinea := '';
  MaxAestimatio := 0;

  if not FileExists(TABULARIUM_SCIENTIA) then
    Exit(FormareResponsum(404, 'Error', 'Scientia non exstat'));

  Verba := TStringList.Create;
  try
    ExtractStrings([' '], [], PChar(VerbaQuery), Verba);
    AssignFile(F, TABULARIUM_SCIENTIA);
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      Aestimatio := 0;
      for I := 0 to Verba.Count - 1 do
      begin
        if Pos(LowerCase(Verba[I]), LowerCase(Linea)) > 0 then
          Inc(Aestimatio);
      end;
      if Aestimatio > MaxAestimatio then
      begin
        MaxAestimatio := Aestimatio;
        MeliorLinea := Linea;
      end;
    end;
    CloseFile(F);
  finally
    Verba.Free;
  end;

  if MaxAestimatio > 0 then
    Result := FormareResponsum(200, 'Successus', MeliorLinea)
  else
    Result := FormareResponsum(404, 'Error', 'Nihil inventum est');
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
    FpClose(CliensSock);
    Exit;
  end;

  DataInput := TStringList.Create;
  try
    ExtractStrings(['|'], [], PChar(LineaData), DataInput);
    Mandatum := '';
    Parametrum1 := '';
    Parametrum2 := '';

    if DataInput.Count > 0 then
      Mandatum := DataInput[0];
    if DataInput.Count > 1 then
      Parametrum1 := DataInput[1];
    if DataInput.Count > 2 then
      Parametrum2 := DataInput[2];

    if Mandatum = 'CREARE_USOREM' then
      Responsum := CreareUsorem(Parametrum1)
    else if Mandatum = 'INTRARE' then
      Responsum := Intrare(Parametrum1)
    else if Mandatum = 'ADDERE_NUNTIUM' then
      Responsum := AddereNuntium(Parametrum1, Parametrum2)
    else if Mandatum = 'LEGENDE_NUNTIOS' then
      Responsum := LegendeNuntios(Parametrum1)
    else if Mandatum = 'INVESTIGARE' then
      Responsum := Investigare(Parametrum1)
    else
      Responsum := FormareResponsum(400, 'Error', 'Mandatum incognitum');

    fpSend(CliensSock, PChar(Responsum), Length(Responsum), 0);
  finally
    DataInput.Free;
    FpClose(CliensSock);
  end;
end;

var
  ServusSock: longint;
  CliensSock: longint;
  Adres: TInetSockAddr;
  Len: longint;
  OptVal: longint;
begin
  WriteLn('Daemonium surgit in portu ', PORTUS, '...');

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

  if fpListen(ServusSock, 10) = -1 then
  begin
    WriteLn('Error: Non potest audire (listen).');
    Halt(1);
  end;

  WriteLn('Daemonium audit...');

  while True do
  begin
    Len := SizeOf(Adres);
    CliensSock := fpAccept(ServusSock, @Adres, @Len);
    if CliensSock <> -1 then
    begin
      WriteLn('Cliens novus advenit.');
      TractareClientem(CliensSock);
    end;
  end;

  FpClose(ServusSock);
end.
