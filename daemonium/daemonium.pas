program Daemonium;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils, sockets, strutils, baseunix, sqldb, pqconnection, db, sha1;

const
  PORTUS = 8080;
  TABULARIUM_SCIENTIA = '../tabularium/scientia/scientia.txt';
  SPIRITUS_MAIL_LOG = '../tabularium/spiritus_mail.log';

var
  DBConn: TPQConnection;
  DBTran: TSQLTransaction;

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

function HashPassword(Pass: String): String;
begin
  // Cryptographically secure hashing with a static salt
  Result := SHA1Print(SHA1String(Pass + 'NecromancerSalt1337'));
end;

procedure InitDatabase;
var
  Host, PortStr, DbName, User, Pass: String;
  Query: TSQLQuery;
  Retries: Integer;
  ConnectedSuccessfully: Boolean;
begin
  Host := GetEnvironmentVariable('DB_HOST');
  if Host = '' then Host := 'db';
  PortStr := GetEnvironmentVariable('DB_PORT');
  if PortStr = '' then PortStr := '5432';
  DbName := GetEnvironmentVariable('DB_NAME');
  if DbName = '' then DbName := 'necromancer';
  User := GetEnvironmentVariable('DB_USER');
  if User = '' then User := 'necromancer';
  Pass := GetEnvironmentVariable('DB_PASS');
  if Pass = '' then Pass := 'necromancer_secret';

  WriteLn('Database Config: Host=', Host, ', Port=', PortStr, ', DB=', DbName, ', User=', User);

  DBConn := TPQConnection.Create(nil);
  DBConn.HostName := Host;
  DBConn.DatabaseName := DbName;
  DBConn.UserName := User;
  DBConn.Password := Pass;
  if PortStr <> '5432' then
    DBConn.Params.Add('port=' + PortStr);

  DBTran := TSQLTransaction.Create(DBConn);
  DBConn.Transaction := DBTran;

  Retries := 0;
  ConnectedSuccessfully := False;

  while (not ConnectedSuccessfully) and (Retries < 15) do
  begin
    try
      Inc(Retries);
      DBConn.Connected := True;
      ConnectedSuccessfully := True;
      WriteLn('[!] Connection to PostgreSQL established successfully.');
    except
      on E: Exception do
      begin
        if Retries >= 15 then
        begin
          WriteLn('[FATAL] Failed to initialize database after 15 attempts: ', E.Message);
          Halt(1);
        end;
        WriteLn('[!] Database connection attempt ', Retries, ' failed. Retrying in 2 seconds...');
        Sleep(2000);
      end;
    end;
  end;

  try
    // Initialize Schema
    Query := TSQLQuery.Create(DBConn);
    Query.Database := DBConn;
    Query.Transaction := DBTran;

    // 1. Users Table
    Query.SQL.Text := 
      'CREATE TABLE IF NOT EXISTS usores (' +
      '  nomen VARCHAR(255) PRIMARY KEY,' +
      '  email VARCHAR(255) UNIQUE,' +
      '  password_hash VARCHAR(255),' +
      '  reg_type VARCHAR(50) NOT NULL,' +
      '  fingerprint VARCHAR(255) NOT NULL,' +
      '  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' +
      ');';
    Query.ExecSQL;

    // 2. User Options Table (UI settings)
    Query.SQL.Text := 
      'CREATE TABLE IF NOT EXISTS optiones (' +
      '  nomen VARCHAR(255) PRIMARY KEY REFERENCES usores(nomen) ON DELETE CASCADE ON UPDATE CASCADE,' +
      '  optiones_json TEXT NOT NULL,' +
      '  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' +
      ');';
    Query.ExecSQL;

    // 3. Chat Messages Table
    Query.SQL.Text := 
      'CREATE TABLE IF NOT EXISTS fabulatio (' +
      '  id SERIAL PRIMARY KEY,' +
      '  nomen VARCHAR(255) NOT NULL REFERENCES usores(nomen) ON DELETE CASCADE ON UPDATE CASCADE,' +
      '  cubiculum VARCHAR(255) NOT NULL DEFAULT ''default'',' +
      '  nuntius TEXT NOT NULL,' +
      '  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' +
      ');';
    Query.ExecSQL;

    // Indexes for high performance
    Query.SQL.Text := 'CREATE INDEX IF NOT EXISTS idx_fabulatio_nomen_cubiculum ON fabulatio(nomen, cubiculum);';
    Query.ExecSQL;

    DBTran.Commit;
    Query.Free;
    WriteLn('[!] Database tables and schema successfully verified/created.');
  except
    on E: Exception do
    begin
      WriteLn('[FATAL] Failed to verify schema: ', E.Message);
      Halt(1);
    end;
  end;
end;

function VerificareFingerprint(Nomen, FP: String): Boolean;
var
  Query: TSQLQuery;
begin
  Result := False;
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT fingerprint FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
      Result := (Query.FieldByName('fingerprint').AsString = FP);
    Query.Close;
  except
    on E: Exception do
      WriteLn('[ERR] VerificareFingerprint: ', E.Message);
  end;
  Query.Free;
end;

function CreareUsorem(Nomen, FP: String): String;
var
  Query: TSQLQuery;
  Invenitur: Boolean;
begin
  Invenitur := False;
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT 1 FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    Invenitur := not Query.EOF;
    Query.Close;

    if Invenitur then
      Result := FormareResponsum(400, 'Error', 'Usor iam exstat')
    else
    begin
      Query.SQL.Text := 'INSERT INTO usores (nomen, reg_type, fingerprint) VALUES (:nomen, ''SPIRITUS'', :fp)';
      Query.ParamByName('nomen').AsString := Nomen;
      Query.ParamByName('fp').AsString := FP;
      Query.ExecSQL;
      DBTran.CommitRetaining;
      Result := FormareResponsum(200, 'Successus', 'Usor creatus est');
    end;
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function Intrare(Nomen, FP: String): String;
var
  Query: TSQLQuery;
  Invenitur: Boolean;
  SavedFP: String;
begin
  Invenitur := False;
  SavedFP := '';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT fingerprint FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
    begin
      Invenitur := True;
      SavedFP := Query.FieldByName('fingerprint').AsString;
    end;
    Query.Close;

    if Invenitur then
    begin
      if SavedFP = FP then
        Result := FormareResponsum(200, 'Successus', 'Introitus permissus')
      else
        Result := FormareResponsum(403, 'Error', 'Fingerprint mismatch / Accessus negatus');
    end
    else
      Result := FormareResponsum(404, 'Error', 'Usor non inventus');
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
end;

function CreareUsoremPlenum(Nomen, Email, Password, FP: String): String;
var
  Query: TSQLQuery;
  ExistensNomen, ExistensEmail: Boolean;
  PassHash: String;
begin
  ExistensNomen := False;
  ExistensEmail := False;
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    // Check nickname
    Query.SQL.Text := 'SELECT 1 FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    ExistensNomen := not Query.EOF;
    Query.Close;

    if ExistensNomen then
      Exit(FormareResponsum(400, 'Error', 'Nomen iam occupatum'));

    // Check email
    Query.SQL.Text := 'SELECT 1 FROM usores WHERE email = :email';
    Query.ParamByName('email').AsString := Email;
    Query.Open;
    ExistensEmail := not Query.EOF;
    Query.Close;

    if ExistensEmail then
      Exit(FormareResponsum(400, 'Error', 'Email iam registratum'));

    PassHash := HashPassword(Password);

    // Save record
    Query.SQL.Text := 'INSERT INTO usores (nomen, email, password_hash, reg_type, fingerprint) ' +
                     'VALUES (:nomen, :email, :pass, ''ANIMA'', :fp)';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('email').AsString := Email;
    Query.ParamByName('pass').AsString := PassHash;
    Query.ParamByName('fp').AsString := FP;
    Query.ExecSQL;
    DBTran.CommitRetaining;

    Result := FormareResponsum(200, 'Successus', 'Anima creata est');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function IntrarePlenum(Email, Password, FP: String): String;
var
  Query: TSQLQuery;
  PassHash: String;
  RegNomen, RegFP, RegPass: String;
  Invenitur: Boolean;
begin
  Result := FormareResponsum(401, 'Error', 'Email vel Password non recte');
  Invenitur := False;
  PassHash := HashPassword(Password);

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT nomen, password_hash, fingerprint FROM usores WHERE email = :email OR nomen = :email';
    Query.ParamByName('email').AsString := Email;
    Query.Open;

    if not Query.EOF then
    begin
      Invenitur := True;
      RegNomen := Query.FieldByName('nomen').AsString;
      RegPass := Query.FieldByName('password_hash').AsString;
      RegFP := Query.FieldByName('fingerprint').AsString;
    end;
    Query.Close;

    if Invenitur then
    begin
      if RegPass = PassHash then
      begin
        if RegFP <> FP then
        begin
          // Update the fingerprint in the database since the password is correct!
          Query.SQL.Text := 'UPDATE usores SET fingerprint = :fp WHERE nomen = :nomen';
          Query.ParamByName('fp').AsString := FP;
          Query.ParamByName('nomen').AsString := RegNomen;
          Query.ExecSQL;
          DBTran.CommitRetaining;
        end;
        Result := FormareResponsum(200, 'Successus', RegNomen);
      end;
    end;
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
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
  Query: TSQLQuery;
begin
  if Cubiculum = '' then Cubiculum := 'default';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'INSERT INTO fabulatio (nomen, cubiculum, nuntius) VALUES (:nomen, :cubiculum, :nuntius)';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('cubiculum').AsString := Cubiculum;
    Query.ParamByName('nuntius').AsString := Nuntius;
    Query.ExecSQL;
    DBTran.CommitRetaining;
    Result := FormareResponsum(200, 'Successus', 'Nuntius additus est');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function LegendeNuntios(Nomen, Cubiculum: String): String;
var
  Query: TSQLQuery;
  Historia: String;
begin
  Historia := '';
  if Cubiculum = '' then Cubiculum := 'default';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT nuntius FROM fabulatio WHERE nomen = :nomen AND cubiculum = :cubiculum ORDER BY id ASC';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('cubiculum').AsString := Cubiculum;
    Query.Open;

    while not Query.EOF do
    begin
      if Historia <> '' then
        Historia := Historia + '\n';
      Historia := Historia + Query.FieldByName('nuntius').AsString;
      Query.Next;
    end;
    Query.Close;

    if Historia = '' then
      Result := FormareResponsum(404, 'Error', 'Historia non inventa')
    else
      Result := FormareResponsum(200, 'Successus', Historia);
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
end;

function IndexFabulationum(Nomen: String): String;
var
  Query: TSQLQuery;
  Lista: String;
  Cub: String;
begin
  Lista := '';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT DISTINCT cubiculum FROM fabulatio WHERE nomen = :nomen ORDER BY cubiculum ASC';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;

    while not Query.EOF do
    begin
      Cub := Query.FieldByName('cubiculum').AsString;
      if Lista <> '' then Lista := Lista + ',';
      Lista := Lista + Cub;
      Query.Next;
    end;
    Query.Close;

    if Lista = '' then
      Result := FormareResponsum(404, 'Error', 'Nihil')
    else
      Result := FormareResponsum(200, 'Successus', Lista);
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
end;

function DeleFabulationem(Nomen, Cubiculum: String): String;
var
  Query: TSQLQuery;
  RowsAffected: Integer;
begin
  if Cubiculum = '' then Cubiculum := 'default';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'DELETE FROM fabulatio WHERE nomen = :nomen AND cubiculum = :cubiculum';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('cubiculum').AsString := Cubiculum;
    Query.ExecSQL;
    RowsAffected := Query.RowsAffected;
    DBTran.CommitRetaining;

    if RowsAffected > 0 then
      Result := FormareResponsum(200, 'Successus', 'Deletum')
    else
      Result := FormareResponsum(404, 'Error', 'Non inventum');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function RenominareFabulationem(Nomen, VetusCubiculum, NovumCubiculum: String): String;
var
  Query: TSQLQuery;
  RowsAffected: Integer;
begin
  if VetusCubiculum = '' then VetusCubiculum := 'default';
  if NovumCubiculum = '' then Exit(FormareResponsum(400, 'Error', 'Novum nomen vacuum est'));

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'UPDATE fabulatio SET cubiculum = :novum WHERE nomen = :nomen AND cubiculum = :vetus';
    Query.ParamByName('novum').AsString := NovumCubiculum;
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('vetus').AsString := VetusCubiculum;
    Query.ExecSQL;
    RowsAffected := Query.RowsAffected;
    DBTran.CommitRetaining;

    if RowsAffected > 0 then
      Result := FormareResponsum(200, 'Successus', 'Renominatum')
    else
      Result := FormareResponsum(404, 'Error', 'Non inventum');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function RenominareUsorem(VetusNomen, NovumNomen: String): String;
var
  Query: TSQLQuery;
  ExistensNomen: Boolean;
begin
  if NovumNomen = '' then Exit(FormareResponsum(400, 'Error', 'Novum nomen vacuum est'));
  ExistensNomen := False;

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    // Check if new nickname is occupied
    Query.SQL.Text := 'SELECT 1 FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := NovumNomen;
    Query.Open;
    ExistensNomen := not Query.EOF;
    Query.Close;

    if ExistensNomen then
      Exit(FormareResponsum(400, 'Error', 'Nomen iam occupatum'));

    // Cascade update will automatically sync all chat rooms and options!
    Query.SQL.Text := 'UPDATE usores SET nomen = :novum WHERE nomen = :vetus';
    Query.ParamByName('novum').AsString := NovumNomen;
    Query.ParamByName('vetus').AsString := VetusNomen;
    Query.ExecSQL;
    DBTran.CommitRetaining;

    Result := FormareResponsum(200, 'Successus', NovumNomen);
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function MutareTessellam(Nomen, VetusPass, NovaPass: String): String;
var
  Query: TSQLQuery;
  VetusPassHash, NovaPassHash: String;
  SavedHash: String;
  Invenitur: Boolean;
begin
  if NovaPass = '' then Exit(FormareResponsum(400, 'Error', 'Nova tessera vacua est'));
  Invenitur := False;
  VetusPassHash := HashPassword(VetusPass);
  NovaPassHash := HashPassword(NovaPass);

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT password_hash FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
    begin
      Invenitur := True;
      SavedHash := Query.FieldByName('password_hash').AsString;
    end;
    Query.Close;

    if Invenitur and (SavedHash = VetusPassHash) then
    begin
      Query.SQL.Text := 'UPDATE usores SET password_hash = :novum WHERE nomen = :nomen';
      Query.ParamByName('novum').AsString := NovaPassHash;
      Query.ParamByName('nomen').AsString := Nomen;
      Query.ExecSQL;
      DBTran.CommitRetaining;
      Result := FormareResponsum(200, 'Successus', 'Tessera mutata est');
    end
    else
      Result := FormareResponsum(401, 'Error', 'Vetus tessera non est recta vel usor non inventus');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function DelereRationem(Nomen: String): String;
var
  Query: TSQLQuery;
begin
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    // Cascade delete automatically deletes options and chat messages!
    Query.SQL.Text := 'DELETE FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ExecSQL;
    DBTran.CommitRetaining;
    Result := FormareResponsum(200, 'Successus', 'Ratio deleta est');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function DelereOmnesFabulationes(Nomen: String): String;
var
  Query: TSQLQuery;
  RowsAffected: Integer;
begin
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'DELETE FROM fabulatio WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ExecSQL;
    RowsAffected := Query.RowsAffected;
    DBTran.CommitRetaining;
    Result := FormareResponsum(200, 'Successus', IntToStr(RowsAffected) + ' fabulationes deletae sunt');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function NumerareNuntiosUsoris(Nomen: String): String;
var
  Query: TSQLQuery;
  Comes: Integer;
begin
  Comes := 0;
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT COUNT(*) FROM fabulatio WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
      Comes := Query.Fields[0].AsInteger;
    Query.Close;
    Result := FormareResponsum(200, 'Successus', IntToStr(Comes));
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
end;

function ServareOptiones(Nomen, Optiones: String): String;
var
  Query: TSQLQuery;
begin
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    // High-performance UPSERT query
    Query.SQL.Text := 'INSERT INTO optiones (nomen, optiones_json) VALUES (:nomen, :json) ' +
                     'ON CONFLICT (nomen) DO UPDATE SET optiones_json = :json, updated_at = CURRENT_TIMESTAMP';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.ParamByName('json').AsString := Optiones;
    Query.ExecSQL;
    DBTran.CommitRetaining;
    Result := FormareResponsum(200, 'Successus', 'Optiones servatae sunt');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function LegereOptiones(Nomen: String): String;
var
  Query: TSQLQuery;
  Optiones: String;
  Invenitur: Boolean;
begin
  Invenitur := False;
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT optiones_json FROM optiones WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
    begin
      Invenitur := True;
      Optiones := Query.FieldByName('optiones_json').AsString;
    end;
    Query.Close;

    if Invenitur then
      Result := FormareResponsum(200, 'Successus', Optiones)
    else
      Result := FormareResponsum(404, 'Error', 'Nullae optiones inventae');
  except
    on E: Exception do
      Result := FormareResponsum(500, 'Error', E.Message);
  end;
  Query.Free;
end;

{ RAG: Investigare in Scientia (Reads knowledgebase text file) }
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
    DataInput.StrictDelimiter := True;
    DataInput.Delimiter := '|';
    DataInput.DelimitedText := LineaData;
    Mandatum := '';
    Parametrum1 := '';
    Parametrum2 := '';

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

  // Connect to PostgreSQL and verify schema
  InitDatabase;

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

  FpClose(ServusSock);
end.
