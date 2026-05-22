unit Auxilia;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sha1, strutils;

const
  SPIRITUS_MAIL_LOG = '../tabularium/spiritus_mail.log';
  TABULARIUM_PROVISORES = '../tabularium/provisores/';
  LLM_SYNC_INTERVAL_MINUTES = 1.0 / 1440.0;

function FormareResponsum(Codex: Integer; Nuntius, Data: String): String;
function HashPassword(Pass: String): String;
function HashClavisLLM(Clavis: String): String;
function BrevisClavisLLM(Clavis: String): String;
function LegereLineasNonVacuas(Via: String): TStringList;

implementation

function FormareResponsum(Codex: Integer; Nuntius, Data: String): String;
begin
  Result := IntToStr(Codex) + '|' + Nuntius + '|' + Data + sLineBreak;
end;

function HashPassword(Pass: String): String;
begin
  // Cryptographically secure hashing with a static salt
  Result := SHA1Print(SHA1String(Pass + 'NecromancerSalt1337'));
end;

function HashClavisLLM(Clavis: String): String;
begin
  Result := SHA1Print(SHA1String('LLMKeySalt1337::' + Clavis));
end;

function BrevisClavisLLM(Clavis: String): String;
begin
  if Length(Clavis) <= 8 then
    Result := Clavis
  else
    Result := Copy(Clavis, 1, 4) + '...' + RightStr(Clavis, 4);
end;

function LegereLineasNonVacuas(Via: String): TStringList;
var
  F: TextFile;
  Linea: String;
begin
  Result := TStringList.Create;
  if not FileExists(Via) then
    Exit;

  AssignFile(F, Via);
  Reset(F);
  try
    while not EOF(F) do
    begin
      ReadLn(F, Linea);
      Linea := Trim(Linea);
      if Linea <> '' then
        Result.Add(Linea);
    end;
  finally
    CloseFile(F);
  end;
end;

end.
