unit Auxilia;

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, sha1, strutils, Cryptographia;

const
  LLM_SYNC_INTERVAL_MINUTES = 1.0 / 1440.0;

function GetSpiritusMailLog: String;
function GetTabulariumProvisores: String;
function FormareResponsum(Codex: Integer; Nuntius, Data: String): String;
function HashPassword(const Pass, Salt: String): String;
function HashClavisLLM(const Clavis: String): String;
function LegacySHA1(const Pass: String): String;
function BrevisClavisLLM(Clavis: String): String;
function LegereLineasNonVacuas(Via: String): TStringList;
function GenerareSalt: String;

implementation

function GetSpiritusMailLog: String;
begin
  Result := GetEnvironmentVariable('SPIRITUS_MAIL_LOG');
  if Result = '' then
    Result := '../tabularium/spiritus_mail.log';
end;

function GetTabulariumProvisores: String;
begin
  Result := GetEnvironmentVariable('TABULARIUM_PROVISORES');
  if Result = '' then
    Result := '../tabularium/provisores/';
end;

function FormareResponsum(Codex: Integer; Nuntius, Data: String): String;
begin
  Result := IntToStr(Codex) + '|' + Nuntius + '|' + Data + sLineBreak;
end;

function HashPassword(const Pass, Salt: String): String;
begin
  // Cryptographically secure hashing with Argon2id using dynamic salt (username or email)
  Result := HashPasswordArgon2(Pass, Salt);
end;

function GenerareSalt: String;
var
  i: Integer;
begin
  Result := '';
  for i := 1 to 32 do
    Result := Result + IntToHex(Random(16), 1);
  Result := LowerCase(Result);
end;

function HashClavisLLM(const Clavis: String): String;
begin
  // Fast Argon2id hashing for LLM provider keys in database
  Result := HashPasswordArgon2('LLMKeySalt1337::' + Clavis, 'LLMKeySalt', 1, 4096, 1);
end;

function LegacySHA1(const Pass: String): String;
begin
  // Legacy SHA1 with static salt for backwards compatibility during migration
  Result := SHA1Print(SHA1String(Pass + 'NecromancerSalt1337'));
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
