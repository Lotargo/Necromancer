unit Scientia;

{$mode objfpc}{$H+}

interface

uses
  classes, sysutils, Auxilia;

const
  TABULARIUM_SCIENTIA = '../tabularium/scientia/scientia.txt';

function Investigare(VerbaQuery: String): String;
function SalvareScientiam(Regula: String): String;

implementation

function SalvareScientiam(Regula: String): String;
var
  F: TextFile;
begin
  if Regula = '' then
    Exit(FormareResponsum(400, 'Error', 'Regula inanis est'));

  ForceDirectories(ExtractFilePath(TABULARIUM_SCIENTIA));

  AssignFile(F, TABULARIUM_SCIENTIA);
  try
    if FileExists(TABULARIUM_SCIENTIA) then
      Append(F)
    else
      Rewrite(F);

    WriteLn(F, Regula);
    Result := FormareResponsum(200, 'Successus', 'Regula salvata est');
  finally
    CloseFile(F);
  end;
end;

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

end.
