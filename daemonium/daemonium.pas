program Daemonium;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils, sockets, strutils, baseunix;

const
  PORTUS = 8080;
  TABULARIUM_USORES = '../tabularium/usores.txt';
  TABULARIUM_SCIENTIA = '../tabularium/scientia/scientia.txt';
  PREFIXUS_FABULATIO = '../tabularium/fabulatio_';
  PREFIXUS_FP = '../tabularium/fp_';
  TABULARIUM_REGISTRUM = '../tabularium/registrum.txt';
  SPIRITUS_MAIL_LOG = '../tabularium/spiritus_mail.log';
  PREFIXUS_OPTIONES = '../tabularium/optiones_';

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

function VerificareFingerprint(Nomen, FP: String): Boolean;
var
  F: TextFile;
  ServatumFP: String;
  NomenFasciculi: String;
begin
  Result := True;
  NomenFasciculi := PREFIXUS_FP + Nomen + '.txt';
  if FileExists(NomenFasciculi) then
  begin
    AssignFile(F, NomenFasciculi);
    Reset(F);
    ReadLn(F, ServatumFP);
    CloseFile(F);
    Result := (ServatumFP = FP);
  end;
end;

procedure ServareFingerprint(Nomen, FP: String);
var
  F: TextFile;
begin
  AssignFile(F, PREFIXUS_FP + Nomen + '.txt');
  Rewrite(F);
  WriteLn(F, FP);
  CloseFile(F);
end;

function CreareUsorem(Nomen, FP: String): String;
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
    ServareFingerprint(Nomen, FP);
    Result := FormareResponsum(200, 'Successus', 'Usor creatus est');
  end;
end;

function Intrare(Nomen, FP: String): String;
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
  begin
    if not FileExists(PREFIXUS_FP + Nomen + '.txt') then
    begin
        ServareFingerprint(Nomen, FP);
        Result := FormareResponsum(200, 'Successus', 'Primo introitus cum fingerprint permissus');
    end
    else if VerificareFingerprint(Nomen, FP) then
      Result := FormareResponsum(200, 'Successus', 'Introitus permissus')
    else
      Result := FormareResponsum(403, 'Error', 'Fingerprint mismatch / Accessus negatus');
  end
  else
    Result := FormareResponsum(404, 'Error', 'Usor non inventus');
end;

function CreareUsoremPlenum(Nomen, Email, Password, FP: String): String;
var
  F: TextFile;
  Linea: String;
  P: Integer;
  ExistensEmail, ExistensNomen: String;
begin
  if FileExists(TABULARIUM_REGISTRUM) then
  begin
    AssignFile(F, TABULARIUM_REGISTRUM);
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      P := Pos('|', Linea);
      ExistensNomen := Copy(Linea, 1, P - 1);
      Linea := Copy(Linea, P + 1, Length(Linea));
      P := Pos('|', Linea);
      ExistensEmail := Copy(Linea, 1, P - 1);
      
      if ExistensNomen = Nomen then
      begin
        CloseFile(F);
        Exit(FormareResponsum(400, 'Error', 'Nomen iam occupatum'));
      end;
      if ExistensEmail = Email then
      begin
        CloseFile(F);
        Exit(FormareResponsum(400, 'Error', 'Email iam registratum'));
      end;
    end;
    CloseFile(F);
  end;

  AssignFile(F, TABULARIUM_REGISTRUM);
  if not FileExists(TABULARIUM_REGISTRUM) then Rewrite(F) else Append(F);
  WriteLn(F, Nomen + '|' + Email + '|' + Password + '|ANIMA|' + FP);
  CloseFile(F);
  
  // Also add to historical usores.txt for legacy support
  CreareUsorem(Nomen, FP);
  
  Result := FormareResponsum(200, 'Successus', 'Anima creata est');
end;

function IntrarePlenum(Email, Password, FP: String): String;
var
  F: TextFile;
  Linea, Pars: String;
  Idx: Integer;
  RegNomen, RegEmail, RegPass, RegType, RegFP: String;
begin
  Result := FormareResponsum(401, 'Error', 'Email vel Password non recte');
  if not FileExists(TABULARIUM_REGISTRUM) then Exit;

  AssignFile(F, TABULARIUM_REGISTRUM);
  Reset(F);
  while not EOF(F) do
  begin
    ReadLn(F, Linea);
    Pars := Linea;
    Idx := Pos('|', Pars); RegNomen := Copy(Pars, 1, Idx-1); Pars := Copy(Pars, Idx+1, Length(Pars));
    Idx := Pos('|', Pars); RegEmail := Copy(Pars, 1, Idx-1); Pars := Copy(Pars, Idx+1, Length(Pars));
    Idx := Pos('|', Pars); RegPass := Copy(Pars, 1, Idx-1); Pars := Copy(Pars, Idx+1, Length(Pars));
    Idx := Pos('|', Pars); RegType := Copy(Pars, 1, Idx-1); Pars := Copy(Pars, Idx+1, Length(Pars));
    RegFP := Pars;

    if (RegEmail = Email) and (RegPass = Password) then
    begin
      CloseFile(F);
      if RegFP <> FP then
        Exit(FormareResponsum(403, 'Error', 'Fingerprint mismatch pro Anima'))
      else
        Exit(FormareResponsum(200, 'Successus', RegNomen));
    end;
  end;
  CloseFile(F);
end;

function PetereRecuperationem(Email: String): String;
var
  F: TextFile;
  Codex: String;
begin
  Randomize;
  Codex := IntToStr(Random(900000) + 100000); // 6-digit code
  AssignFile(F, SPIRITUS_MAIL_LOG);
  if not FileExists(SPIRITUS_MAIL_LOG) then Rewrite(F) else Append(F);
  WriteLn(F, DateTimeToStr(Now) + ' | ' + Email + ' | CODE: ' + Codex);
  CloseFile(F);
  Result := FormareResponsum(200, 'Successus', 'Codex missus est (check log)');
end;

function AddereNuntium(Nomen, Cubiculum, Nuntius: String): String;
var
  F: TextFile;
  NomenFasciculi: String;
begin
  if Cubiculum = '' then Cubiculum := 'default';
  NomenFasciculi := PREFIXUS_FABULATIO + Nomen + '_' + Cubiculum + '.txt';
  AssignFile(F, NomenFasciculi);
  if FileExists(NomenFasciculi) then
    Append(F)
  else
    Rewrite(F);
  WriteLn(F, Nuntius);
  CloseFile(F);
  Result := FormareResponsum(200, 'Successus', 'Nuntius additus est');
end;

function LegendeNuntios(Nomen, Cubiculum: String): String;
var
  F: TextFile;
  NomenFasciculi: String;
  Linea: String;
  Historia: String;
begin
  Historia := '';
  if Cubiculum = '' then Cubiculum := 'default';
  NomenFasciculi := PREFIXUS_FABULATIO + Nomen + '_' + Cubiculum + '.txt';
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

function IndexFabulationum(Nomen: String): String;
var
  SR: TSearchRec;
  Prefix: String;
  Lista: String;
  Cubiculum: String;
begin
  Lista := '';
  Prefix := 'fabulatio_' + Nomen + '_';
  if FindFirst('../tabularium/' + Prefix + '*.txt', faAnyFile, SR) = 0 then
  begin
    repeat
      Cubiculum := Copy(SR.Name, Length(Prefix) + 1, Length(SR.Name) - Length(Prefix) - 4);
      if Lista <> '' then Lista := Lista + ',';
      Lista := Lista + Cubiculum;
    until FindNext(SR) <> 0;
    FindClose(SR);
  end;
  
  if Lista = '' then
    Result := FormareResponsum(404, 'Error', 'Nihil')
  else
    Result := FormareResponsum(200, 'Successus', Lista);
end;

function DeleFabulationem(Nomen, Cubiculum: String): String;
var
  NomenFasciculi: String;
begin
  if Cubiculum = '' then Cubiculum := 'default';
  NomenFasciculi := PREFIXUS_FABULATIO + Nomen + '_' + Cubiculum + '.txt';
  if FileExists(NomenFasciculi) then
  begin
    DeleteFile(NomenFasciculi);
    Result := FormareResponsum(200, 'Successus', 'Deletum');
  end
  else
    Result := FormareResponsum(404, 'Error', 'Non inventum');
end;

function RenominareFabulationem(Nomen, VetusCubiculum, NovumCubiculum: String): String;
var
  VetusNomen, NovumNomen: String;
begin
  if VetusCubiculum = '' then VetusCubiculum := 'default';
  if NovumCubiculum = '' then Exit(FormareResponsum(400, 'Error', 'Novum nomen vacuum est'));
  
  VetusNomen := PREFIXUS_FABULATIO + Nomen + '_' + VetusCubiculum + '.txt';
  NovumNomen := PREFIXUS_FABULATIO + Nomen + '_' + NovumCubiculum + '.txt';
  
  if FileExists(VetusNomen) then
  begin
    if RenameFile(VetusNomen, NovumNomen) then
      Result := FormareResponsum(200, 'Successus', 'Renominatum')
    else
      Result := FormareResponsum(500, 'Error', 'Non potest renominare');
  end
  else
    Result := FormareResponsum(404, 'Error', 'Non inventum');
end;

function RenominareUsorem(VetusNomen, NovumNomen: String): String;
var
  F, TempF: TextFile;
  Linea, TempLinea: String;
  P: Integer;
  ExistensEmail, ExistensNomen, Rest: String;
  SR: TSearchRec;
  VetusFabulatio, NovaFabulatio: String;
begin
  if NovumNomen = '' then Exit(FormareResponsum(400, 'Error', 'Novum nomen vacuum est'));

  // 1. Check if new name already exists in registrum.txt
  if FileExists(TABULARIUM_REGISTRUM) then
  begin
    AssignFile(F, TABULARIUM_REGISTRUM);
    Reset(F);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      P := Pos('|', Linea);
      if P > 0 then
      begin
        ExistensNomen := Copy(Linea, 1, P - 1);
        if ExistensNomen = NovumNomen then
        begin
          CloseFile(F);
          Exit(FormareResponsum(400, 'Error', 'Nomen iam occupatum'));
        end;
      end;
    end;
    CloseFile(F);
  end;

  // 2. Update usores.txt
  if FileExists(TABULARIUM_USORES) then
  begin
    AssignFile(F, TABULARIUM_USORES);
    Reset(F);
    AssignFile(TempF, TABULARIUM_USORES + '.tmp');
    Rewrite(TempF);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      if Linea = VetusNomen then
        WriteLn(TempF, NovumNomen)
      else
        WriteLn(TempF, Linea);
    end;
    CloseFile(F);
    CloseFile(TempF);
    DeleteFile(TABULARIUM_USORES);
    RenameFile(TABULARIUM_USORES + '.tmp', TABULARIUM_USORES);
  end;

  // 3. Update registrum.txt
  if FileExists(TABULARIUM_REGISTRUM) then
  begin
    AssignFile(F, TABULARIUM_REGISTRUM);
    Reset(F);
    AssignFile(TempF, TABULARIUM_REGISTRUM + '.tmp');
    Rewrite(TempF);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      P := Pos('|', Linea);
      if P > 0 then
      begin
        ExistensNomen := Copy(Linea, 1, P - 1);
        Rest := Copy(Linea, P + 1, Length(Linea));
        if ExistensNomen = VetusNomen then
          WriteLn(TempF, NovumNomen + '|' + Rest)
        else
          WriteLn(TempF, Linea);
      end;
    end;
    CloseFile(F);
    CloseFile(TempF);
    DeleteFile(TABULARIUM_REGISTRUM);
    RenameFile(TABULARIUM_REGISTRUM + '.tmp', TABULARIUM_REGISTRUM);
  end;

  // 4. Rename fingerprint file
  if FileExists(PREFIXUS_FP + VetusNomen + '.txt') then
    RenameFile(PREFIXUS_FP + VetusNomen + '.txt', PREFIXUS_FP + NovumNomen + '.txt');

  // 4b. Rename optiones file
  if FileExists(PREFIXUS_OPTIONES + VetusNomen + '.txt') then
    RenameFile(PREFIXUS_OPTIONES + VetusNomen + '.txt', PREFIXUS_OPTIONES + NovumNomen + '.txt');

  // 5. Rename all fabulatio files
  if FindFirst(PREFIXUS_FABULATIO + VetusNomen + '_*.txt', faAnyFile, SR) = 0 then
  begin
    repeat
      VetusFabulatio := '../tabularium/' + SR.Name;
      NovaFabulatio := StringReplace(VetusFabulatio, PREFIXUS_FABULATIO + VetusNomen + '_', PREFIXUS_FABULATIO + NovumNomen + '_', []);
      RenameFile(VetusFabulatio, NovaFabulatio);
    until FindNext(SR) <> 0;
    FindClose(SR);
  end;

  Result := FormareResponsum(200, 'Successus', NovumNomen);
end;

function MutareTessellam(Nomen, VetusPass, NovaPass: String): String;
var
  F, TempF: TextFile;
  Linea, Rest: String;
  P: Integer;
  ExistensNomen, ExistensEmail, ExistensPass: String;
  Mutatum: Boolean;
begin
  if NovaPass = '' then Exit(FormareResponsum(400, 'Error', 'Nova tessera vacua est'));
  if not FileExists(TABULARIUM_REGISTRUM) then Exit(FormareResponsum(404, 'Error', 'Registrum non exstat'));

  Mutatum := False;
  AssignFile(F, TABULARIUM_REGISTRUM);
  Reset(F);
  AssignFile(TempF, TABULARIUM_REGISTRUM + '.tmp');
  Rewrite(TempF);

  while not EOF(F) do
  begin
    ReadLn(F, Linea);
    Rest := Linea;

    P := Pos('|', Rest); ExistensNomen := Copy(Rest, 1, P - 1); Rest := Copy(Rest, P + 1, Length(Rest));
    P := Pos('|', Rest); ExistensEmail := Copy(Rest, 1, P - 1); Rest := Copy(Rest, P + 1, Length(Rest));
    P := Pos('|', Rest); ExistensPass := Copy(Rest, 1, P - 1); Rest := Copy(Rest, P + 1, Length(Rest));

    if (ExistensNomen = Nomen) and (ExistensPass = VetusPass) then
    begin
      WriteLn(TempF, ExistensNomen + '|' + ExistensEmail + '|' + NovaPass + '|' + Rest);
      Mutatum := True;
    end
    else
      WriteLn(TempF, Linea);
  end;

  CloseFile(F);
  CloseFile(TempF);

  if Mutatum then
  begin
    DeleteFile(TABULARIUM_REGISTRUM);
    RenameFile(TABULARIUM_REGISTRUM + '.tmp', TABULARIUM_REGISTRUM);
    Result := FormareResponsum(200, 'Successus', 'Tessera mutata est');
  end
  else
  begin
    DeleteFile(TABULARIUM_REGISTRUM + '.tmp');
    Result := FormareResponsum(401, 'Error', 'Vetus tessera non est recta vel usor non inventus');
  end;
end;

function DelereRationem(Nomen: String): String;
var
  F, TempF: TextFile;
  Linea: String;
  P: Integer;
  ExistensNomen: String;
  SR: TSearchRec;
begin
  // 1. Remove from usores.txt
  if FileExists(TABULARIUM_USORES) then
  begin
    AssignFile(F, TABULARIUM_USORES);
    Reset(F);
    AssignFile(TempF, TABULARIUM_USORES + '.tmp');
    Rewrite(TempF);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      if Linea <> Nomen then WriteLn(TempF, Linea);
    end;
    CloseFile(F);
    CloseFile(TempF);
    DeleteFile(TABULARIUM_USORES);
    RenameFile(TABULARIUM_USORES + '.tmp', TABULARIUM_USORES);
  end;

  // 2. Remove from registrum.txt
  if FileExists(TABULARIUM_REGISTRUM) then
  begin
    AssignFile(F, TABULARIUM_REGISTRUM);
    Reset(F);
    AssignFile(TempF, TABULARIUM_REGISTRUM + '.tmp');
    Rewrite(TempF);
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      P := Pos('|', Linea);
      if P > 0 then ExistensNomen := Copy(Linea, 1, P - 1) else ExistensNomen := Linea;
      if ExistensNomen <> Nomen then WriteLn(TempF, Linea);
    end;
    CloseFile(F);
    CloseFile(TempF);
    DeleteFile(TABULARIUM_REGISTRUM);
    RenameFile(TABULARIUM_REGISTRUM + '.tmp', TABULARIUM_REGISTRUM);
  end;

  // 3. Delete fingerprint file
  if FileExists(PREFIXUS_FP + Nomen + '.txt') then
    DeleteFile(PREFIXUS_FP + Nomen + '.txt');

  // 3b. Delete optiones file
  if FileExists(PREFIXUS_OPTIONES + Nomen + '.txt') then
    DeleteFile(PREFIXUS_OPTIONES + Nomen + '.txt');

  // 4. Delete all fabulatio files
  if FindFirst(PREFIXUS_FABULATIO + Nomen + '_*.txt', faAnyFile, SR) = 0 then
  begin
    repeat
      DeleteFile('../tabularium/' + SR.Name);
    until FindNext(SR) <> 0;
    FindClose(SR);
  end;

  Result := FormareResponsum(200, 'Successus', 'Ratio deleta est');
end;

function DelereOmnesFabulationes(Nomen: String): String;
var
  SR: TSearchRec;
  Comes: Integer;
begin
  Comes := 0;
  if FindFirst(PREFIXUS_FABULATIO + Nomen + '_*.txt', faAnyFile, SR) = 0 then
  begin
    repeat
      if DeleteFile('../tabularium/' + SR.Name) then Inc(Comes);
    until FindNext(SR) <> 0;
    FindClose(SR);
  end;

  Result := FormareResponsum(200, 'Successus', IntToStr(Comes) + ' fabulationes deletae sunt');
end;

function NumerareNuntiosUsoris(Nomen: String): String;
var
  SR: TSearchRec;
  F: TextFile;
  Comes: Integer;
begin
  Comes := 0;
  if FindFirst(PREFIXUS_FABULATIO + Nomen + '_*.txt', faAnyFile, SR) = 0 then
  begin
    repeat
      AssignFile(F, '../tabularium/' + SR.Name);
      Reset(F);
      while not EOF(F) do
      begin
        ReadLn(F);
        Inc(Comes);
      end;
      CloseFile(F);
    until FindNext(SR) <> 0;
    FindClose(SR);
  end;
  Result := FormareResponsum(200, 'Successus', IntToStr(Comes));
end;

function ServareOptiones(Nomen, Optiones: String): String;
var
  F: TextFile;
begin
  AssignFile(F, PREFIXUS_OPTIONES + Nomen + '.txt');
  Rewrite(F);
  WriteLn(F, Optiones);
  CloseFile(F);
  Result := FormareResponsum(200, 'Successus', 'Optiones servatae sunt');
end;

function LegereOptiones(Nomen: String): String;
var
  F: TextFile;
  Optiones: String;
begin
  if FileExists(PREFIXUS_OPTIONES + Nomen + '.txt') then
  begin
    AssignFile(F, PREFIXUS_OPTIONES + Nomen + '.txt');
    Reset(F);
    ReadLn(F, Optiones);
    CloseFile(F);
    Result := FormareResponsum(200, 'Successus', Optiones);
  end
  else
    Result := FormareResponsum(404, 'Error', 'Nullae optiones inventae');
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
  Mandatum, Parametrum1, Parametrum2, Parametrum3: String;
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
    DataInput.StrictDelimiter := True;
    DataInput.Delimiter := '|';
    DataInput.DelimitedText := LineaData;
    Mandatum := '';
    Parametrum1 := '';
    Parametrum2 := '';
    Parametrum3 := '';

    if DataInput.Count > 0 then Mandatum := DataInput[0];
    if DataInput.Count > 1 then Parametrum1 := DataInput[1];
    if DataInput.Count > 2 then Parametrum2 := DataInput[2];
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
  WriteLn('------------------------------------------------');
  WriteLn(' [!] ДЕМОН ПРОБУДИЛСЯ / DAEMON AWAKENED (Process #', fpgetpid, ')');
  WriteLn(' [!] ВНИМАНИЕ: Если ты это читаешь, значит ты полез в логи докера. Зачем?');
  WriteLn(' [!] WARNING: 640KB RAM IS ENOUGH FOR EVERYONE (Currently using: ', GetFPCHeapStatus.CurrHeapUsed div 1024, 'KB)');
  WriteLn(' [!] ГОД ОТ РОЖДЕСТВА ХРИСТОВА / YEAR: ', FormatDateTime('yyyy', Now));
  WriteLn('------------------------------------------------');
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

  if fpListen(ServusSock, 10) = -1 then
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

  FpClose(ServusSock);
end.
