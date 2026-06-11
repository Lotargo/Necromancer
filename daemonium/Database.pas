unit Database;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sqldb, pqconnection, db;

var
  DBConn: TPQConnection;
  DBTran: TSQLTransaction;

procedure InitDatabase;

implementation

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
  if Pass = '' then
  begin
    WriteLn('[FATAL] Environment variable DB_PASS is required but was not set.');
    Halt(1);
  end;

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

    Query.SQL.Text := 'ALTER TABLE usores ADD COLUMN IF NOT EXISTS password_salt VARCHAR(255);';
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

    Query.SQL.Text :=
      'CREATE TABLE IF NOT EXISTS llm_key_status (' +
      '  provider VARCHAR(128) NOT NULL,' +
      '  key_hash VARCHAR(64) NOT NULL,' +
      '  key_hint VARCHAR(32) NOT NULL,' +
      '  status VARCHAR(32) NOT NULL DEFAULT ''active'',' +
      '  quarantine_until TIMESTAMP NULL,' +
      '  disabled_reason TEXT,' +
      '  last_http_code INTEGER,' +
      '  last_error_kind VARCHAR(128),' +
      '  success_count INTEGER NOT NULL DEFAULT 0,' +
      '  failure_count INTEGER NOT NULL DEFAULT 0,' +
      '  last_success_at TIMESTAMP NULL,' +
      '  last_failure_at TIMESTAMP NULL,' +
      '  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,' +
      '  PRIMARY KEY (provider, key_hash)' +
      ');';
    Query.ExecSQL;

    Query.SQL.Text :=
      'CREATE TABLE IF NOT EXISTS llm_key_events (' +
      '  id SERIAL PRIMARY KEY,' +
      '  provider VARCHAR(128) NOT NULL,' +
      '  key_hash VARCHAR(64) NOT NULL,' +
      '  key_hint VARCHAR(32) NOT NULL,' +
      '  model VARCHAR(255),' +
      '  event_type VARCHAR(64) NOT NULL,' +
      '  http_code INTEGER,' +
      '  error_kind VARCHAR(128),' +
      '  detail TEXT,' +
      '  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' +
      ');';
    Query.ExecSQL;

    Query.SQL.Text := 'CREATE INDEX IF NOT EXISTS idx_llm_key_events_lookup ON llm_key_events(provider, key_hash, created_at);';
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

end.
