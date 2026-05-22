unit ClavesLlm;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sqldb, db, Database, Auxilia;

var
  UltimaSyncClavium: TStringList;

function SynchronizareClavesLLMProvider(Provider: String; ForceSync: Boolean = False): String;
procedure SynchronizareOmnesClavesLLM;
function StatumClavisLLM(Provider, Clavis: String): String;
function NotareEventumClavisLLM(Provider, Clavis, Model, EventType, HttpCodeText, ErrorKind, Detail: String): String;

implementation

function IndexUltimaSyncProvider(Provider: String): Integer;
begin
  Result := UltimaSyncClavium.IndexOfName(Provider);
end;

function DebetSynchronizareProvider(Provider: String): Boolean;
var
  Idx: Integer;
  Ultima: TDateTime;
begin
  Result := True;
  Idx := IndexUltimaSyncProvider(Provider);
  if Idx <> -1 then
  begin
    Ultima := StrToFloatDef(UltimaSyncClavium.ValueFromIndex[Idx], 0);
    if (Ultima > 0) and ((Now - Ultima) < LLM_SYNC_INTERVAL_MINUTES) then
      Result := False;
  end;
end;

procedure MemorareSyncProvider(Provider: String);
var
  Idx: Integer;
begin
  Idx := IndexUltimaSyncProvider(Provider);
  if Idx = -1 then
    UltimaSyncClavium.Add(Provider + '=' + FloatToStr(Now))
  else
    UltimaSyncClavium.ValueFromIndex[Idx] := FloatToStr(Now);
end;

function SynchronizareClavesLLMProvider(Provider: String; ForceSync: Boolean = False): String;
var
  Query: TSQLQuery;
  Claves, Hashes, Hints: TStringList;
  I: Integer;
  KeyHash, KeyHint, ProviderDir, ClavesPath, InClause: String;
begin
  if (not ForceSync) and (not DebetSynchronizareProvider(Provider)) then
    Exit(FormareResponsum(200, 'Successus', 'Synchronizatio recens iam facta est'));

  ProviderDir := TABULARIUM_PROVISORES + Provider + '/';
  ClavesPath := ProviderDir + 'claves.txt';

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  Claves := LegereLineasNonVacuas(ClavesPath);
  Hashes := TStringList.Create;
  Hints := TStringList.Create;
  try
    for I := 0 to Claves.Count - 1 do
    begin
      KeyHash := HashClavisLLM(Claves[I]);
      KeyHint := BrevisClavisLLM(Claves[I]);
      Hashes.Add(KeyHash);
      Hints.Add(KeyHint);

      Query.SQL.Text :=
        'INSERT INTO llm_key_status (provider, key_hash, key_hint, status, updated_at) ' +
        'VALUES (:provider, :key_hash, :key_hint, ''active'', CURRENT_TIMESTAMP) ' +
        'ON CONFLICT (provider, key_hash) DO UPDATE SET key_hint = EXCLUDED.key_hint, updated_at = CURRENT_TIMESTAMP';
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ParamByName('key_hint').AsString := KeyHint;
      Query.ExecSQL;
    end;

    if Hashes.Count > 0 then
    begin
      InClause := '';
      for I := 0 to Hashes.Count - 1 do
      begin
        if InClause <> '' then
          InClause := InClause + ',';
        InClause := InClause + QuotedStr(Hashes[I]);
      end;

      Query.SQL.Text := 'DELETE FROM llm_key_events WHERE provider = :provider AND key_hash NOT IN (' + InClause + ')';
      Query.ParamByName('provider').AsString := Provider;
      Query.ExecSQL;

      Query.SQL.Text := 'DELETE FROM llm_key_status WHERE provider = :provider AND key_hash NOT IN (' + InClause + ')';
      Query.ParamByName('provider').AsString := Provider;
      Query.ExecSQL;
    end
    else
    begin
      Query.SQL.Text := 'DELETE FROM llm_key_events WHERE provider = :provider';
      Query.ParamByName('provider').AsString := Provider;
      Query.ExecSQL;

      Query.SQL.Text := 'DELETE FROM llm_key_status WHERE provider = :provider';
      Query.ParamByName('provider').AsString := Provider;
      Query.ExecSQL;
    end;

    DBTran.CommitRetaining;
    MemorareSyncProvider(Provider);
    Result := FormareResponsum(200, 'Successus', IntToStr(Claves.Count) + ' claves synchronizatae sunt');
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Hints.Free;
  Hashes.Free;
  Claves.Free;
  Query.Free;
end;

procedure SynchronizareOmnesClavesLLM;
begin
  SynchronizareClavesLLMProvider('gemini', True);
  SynchronizareClavesLLMProvider('groq', True);
  SynchronizareClavesLLMProvider('cerebras', True);
end;

function StatumClavisLLM(Provider, Clavis: String): String;
var
  Query: TSQLQuery;
  KeyHash: String;
  StatusValue: String;
begin
  Result := FormareResponsum(200, 'Successus', 'active');
  SynchronizareClavesLLMProvider(Provider);
  KeyHash := HashClavisLLM(Clavis);
  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text :=
      'UPDATE llm_key_status ' +
      'SET status = ''active'', quarantine_until = NULL, disabled_reason = NULL, updated_at = CURRENT_TIMESTAMP ' +
      'WHERE provider = :provider AND key_hash = :key_hash AND status = ''resting'' AND quarantine_until IS NOT NULL AND quarantine_until <= CURRENT_TIMESTAMP';
    Query.ParamByName('provider').AsString := Provider;
    Query.ParamByName('key_hash').AsString := KeyHash;
    Query.ExecSQL;
    DBTran.CommitRetaining;

    Query.SQL.Text :=
      'SELECT status FROM llm_key_status WHERE provider = :provider AND key_hash = :key_hash';
    Query.ParamByName('provider').AsString := Provider;
    Query.ParamByName('key_hash').AsString := KeyHash;
    Query.Open;
    if not Query.EOF then
    begin
      StatusValue := Query.FieldByName('status').AsString;
      Result := FormareResponsum(200, 'Successus', StatusValue);
    end;
    Query.Close;
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

function NotareEventumClavisLLM(Provider, Clavis, Model, EventType, HttpCodeText, ErrorKind, Detail: String): String;
var
  Query: TSQLQuery;
  KeyHash, KeyHint: String;
  HttpCodeValue: Integer;
  RateLimitRecentCount: Integer;
begin
  Result := FormareResponsum(200, 'Successus', 'Eventum notatum est');
  SynchronizareClavesLLMProvider(Provider);
  KeyHash := HashClavisLLM(Clavis);
  KeyHint := BrevisClavisLLM(Clavis);
  HttpCodeValue := StrToIntDef(HttpCodeText, 0);

  Query := TSQLQuery.Create(DBConn);
  Query.Database := DBConn;
  Query.Transaction := DBTran;
  try
    Query.SQL.Text :=
      'INSERT INTO llm_key_status (provider, key_hash, key_hint) VALUES (:provider, :key_hash, :key_hint) ' +
      'ON CONFLICT (provider, key_hash) DO NOTHING';
    Query.ParamByName('provider').AsString := Provider;
    Query.ParamByName('key_hash').AsString := KeyHash;
    Query.ParamByName('key_hint').AsString := KeyHint;
    Query.ExecSQL;

    Query.SQL.Text :=
      'INSERT INTO llm_key_events (provider, key_hash, key_hint, model, event_type, http_code, error_kind, detail) ' +
      'VALUES (:provider, :key_hash, :key_hint, :model, :event_type, :http_code, :error_kind, :detail)';
    Query.ParamByName('provider').AsString := Provider;
    Query.ParamByName('key_hash').AsString := KeyHash;
    Query.ParamByName('key_hint').AsString := KeyHint;
    Query.ParamByName('model').AsString := Model;
    Query.ParamByName('event_type').AsString := EventType;
    Query.ParamByName('http_code').AsInteger := HttpCodeValue;
    Query.ParamByName('error_kind').AsString := ErrorKind;
    Query.ParamByName('detail').AsString := Detail;
    Query.ExecSQL;

    if EventType = 'SUCCESS' then
    begin
      Query.SQL.Text :=
        'UPDATE llm_key_status SET ' +
        'status = ''active'', quarantine_until = NULL, disabled_reason = NULL, last_http_code = :http_code, ' +
        'last_error_kind = NULL, success_count = success_count + 1, last_success_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
        'WHERE provider = :provider AND key_hash = :key_hash';
      Query.ParamByName('http_code').AsInteger := HttpCodeValue;
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ExecSQL;
    end
    else if EventType = 'RATE_LIMIT' then
    begin
      Query.SQL.Text :=
        'SELECT COUNT(*) AS cnt FROM llm_key_events ' +
        'WHERE provider = :provider AND key_hash = :key_hash AND event_type = ''RATE_LIMIT'' ' +
        'AND created_at >= (CURRENT_TIMESTAMP - INTERVAL ''30 minutes'')';
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.Open;
      RateLimitRecentCount := Query.FieldByName('cnt').AsInteger;
      Query.Close;

      if RateLimitRecentCount >= 2 then
      begin
        Query.SQL.Text :=
          'UPDATE llm_key_status SET ' +
          'status = ''resting'', quarantine_until = (CURRENT_TIMESTAMP + INTERVAL ''30 minutes''), disabled_reason = :disabled_reason, ' +
          'last_http_code = :http_code, last_error_kind = :error_kind, failure_count = failure_count + 1, ' +
          'last_failure_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
          'WHERE provider = :provider AND key_hash = :key_hash';
      end
      else
      begin
        Query.SQL.Text :=
          'UPDATE llm_key_status SET ' +
          'last_http_code = :http_code, last_error_kind = :error_kind, failure_count = failure_count + 1, ' +
          'last_failure_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
          'WHERE provider = :provider AND key_hash = :key_hash';
      end;
      Query.ParamByName('disabled_reason').AsString := 'rate_limit_rest';
      Query.ParamByName('http_code').AsInteger := HttpCodeValue;
      Query.ParamByName('error_kind').AsString := ErrorKind;
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ExecSQL;
    end
    else if EventType = 'REGION_BLOCKED' then
    begin
      Query.SQL.Text :=
        'UPDATE llm_key_status SET ' +
        'status = ''resting'', quarantine_until = (CURRENT_TIMESTAMP + INTERVAL ''30 minutes''), disabled_reason = :disabled_reason, ' +
        'last_http_code = :http_code, last_error_kind = :error_kind, failure_count = failure_count + 1, ' +
        'last_failure_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
        'WHERE provider = :provider AND key_hash = :key_hash';
      Query.ParamByName('disabled_reason').AsString := 'region_blocked';
      Query.ParamByName('http_code').AsInteger := HttpCodeValue;
      Query.ParamByName('error_kind').AsString := ErrorKind;
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ExecSQL;
    end
    else if EventType = 'DISABLE' then
    begin
      Query.SQL.Text :=
        'UPDATE llm_key_status SET ' +
        'status = ''disabled'', quarantine_until = NULL, disabled_reason = :disabled_reason, ' +
        'last_http_code = :http_code, last_error_kind = :error_kind, failure_count = failure_count + 1, ' +
        'last_failure_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
        'WHERE provider = :provider AND key_hash = :key_hash';
      Query.ParamByName('disabled_reason').AsString := ErrorKind;
      Query.ParamByName('http_code').AsInteger := HttpCodeValue;
      Query.ParamByName('error_kind').AsString := ErrorKind;
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ExecSQL;
    end
    else
    begin
      Query.SQL.Text :=
        'UPDATE llm_key_status SET ' +
        'last_http_code = :http_code, last_error_kind = :error_kind, failure_count = failure_count + 1, ' +
        'last_failure_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP ' +
        'WHERE provider = :provider AND key_hash = :key_hash';
      Query.ParamByName('http_code').AsInteger := HttpCodeValue;
      Query.ParamByName('error_kind').AsString := ErrorKind;
      Query.ParamByName('provider').AsString := Provider;
      Query.ParamByName('key_hash').AsString := KeyHash;
      Query.ExecSQL;
    end;

    DBTran.CommitRetaining;
    WriteLn('[LLM KEY] provider=', Provider, ' key=', KeyHint, ' event=', EventType, ' http=', HttpCodeValue, ' kind=', ErrorKind);
  except
    on E: Exception do
    begin
      DBTran.RollbackRetaining;
      Result := FormareResponsum(500, 'Error', E.Message);
    end;
  end;
  Query.Free;
end;

initialization
  UltimaSyncClavium := TStringList.Create;
finalization
  UltimaSyncClavium.Free;

end.
