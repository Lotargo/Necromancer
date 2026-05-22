unit Usores;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sqldb, db, Database, Auxilia;

function VerificareFingerprint(Nomen, FP: String): Boolean;
function CreareUsorem(Nomen, FP: String): String;
function Intrare(Nomen, FP: String): String;
function CreareUsoremPlenum(Nomen, Email, Password, FP: String): String;
function IntrarePlenum(Email, Password, FP: String): String;
function PetereRecuperationem(Email: String): String;
function RenominareUsorem(VetusNomen, NovumNomen: String): String;
function MutareTessellam(Nomen, VetusPass, NovaPass: String): String;
function DelereRationem(Nomen: String): String;

implementation

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
  RegType: String;
begin
  Invenitur := False;
  RegType := '';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text := 'SELECT reg_type FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
    begin
      Invenitur := True;
      RegType := Query.FieldByName('reg_type').AsString;
    end;
    Query.Close;

    if Invenitur then
    begin
      if RegType = 'ANIMA' then
        Result := FormareResponsum(403, 'Error', 'Usor passwordum requirit (Use ANIMA mode)')
      else
      begin
        // SPIRITUS: update fingerprint to current browser on every login
        Query.SQL.Text := 'UPDATE usores SET fingerprint = :fp WHERE nomen = :nomen';
        Query.ParamByName('fp').AsString := FP;
        Query.ParamByName('nomen').AsString := Nomen;
        Query.ExecSQL;
        DBTran.CommitRetaining;
        Result := FormareResponsum(200, 'Successus', 'Introitus permissus');
      end;
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
  ExistensRegType: String;
  PassHash: String;
begin
  ExistensNomen := False;
  ExistensEmail := False;
  ExistensRegType := '';
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    // Check nickname and its registration type
    Query.SQL.Text := 'SELECT reg_type FROM usores WHERE nomen = :nomen';
    Query.ParamByName('nomen').AsString := Nomen;
    Query.Open;
    if not Query.EOF then
    begin
      ExistensNomen := True;
      ExistensRegType := Query.FieldByName('reg_type').AsString;
    end;
    Query.Close;

    // Check email
    Query.SQL.Text := 'SELECT 1 FROM usores WHERE email = :email';
    Query.ParamByName('email').AsString := Email;
    Query.Open;
    ExistensEmail := not Query.EOF;
    Query.Close;

    if ExistensEmail then
      Exit(FormareResponsum(400, 'Error', 'Email iam registratum'));

    PassHash := HashPassword(Password);

    if ExistensNomen then
    begin
      if ExistensRegType = 'SPIRITUS' then
      begin
        // Upgrade temporary guest user to full ANIMA user!
        Query.SQL.Text := 'UPDATE usores SET email = :email, password_hash = :pass, reg_type = ''ANIMA'', fingerprint = :fp WHERE nomen = :nomen';
        Query.ParamByName('email').AsString := Email;
        Query.ParamByName('pass').AsString := PassHash;
        Query.ParamByName('fp').AsString := FP;
        Query.ParamByName('nomen').AsString := Nomen;
        Query.ExecSQL;
        DBTran.CommitRetaining;
        Exit(FormareResponsum(200, 'Successus', 'Anima creata est'));
      end
      else
        Exit(FormareResponsum(400, 'Error', 'Nomen iam occupatum'));
    end;

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

end.
