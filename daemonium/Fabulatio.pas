unit Fabulatio;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sqldb, db, Database, Auxilia;

function AddereNuntium(Nomen, Cubiculum, Nuntius: String): String;
function LegendeNuntios(Nomen, Cubiculum: String): String;
function IndexFabulationum(Nomen: String): String;
function DeleFabulationem(Nomen, Cubiculum: String): String;
function RenominareFabulationem(Nomen, VetusCubiculum, NovumCubiculum: String): String;
function DelereOmnesFabulationes(Nomen: String): String;
function NumerareNuntiosUsoris(Nomen: String): String;
function ServareOptiones(Nomen, Optiones: String): String;
function LegereOptiones(Nomen: String): String;

implementation

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
        Historia := Historia + '[NUNTIUS_SEP]';
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

end.
