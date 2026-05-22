unit Cryptographia;

{$mode objfpc}{$H+}

interface

uses
  SysUtils,
  HlpHashLibTypes,
  HlpConverters,
  HlpArgon2TypeAndVersion,
  HlpHashFactory,
  HlpIHashInfo,
  HlpPBKDF_Argon2NotBuildInAdapter;

function HashPasswordArgon2(const Password, Salt: String; Iterations: Integer = 2; MemoryAsKB: Integer = 65536; Parallelism: Integer = 2): String;

implementation

function HashPasswordArgon2(const Password, Salt: String; Iterations: Integer = 2; MemoryAsKB: Integer = 65536; Parallelism: Integer = 2): String;
var
  PasswordBytes, SaltBytes, HashBytes: THashLibByteArray;
  ParamsBuilder: IArgon2ParametersBuilder;
  Params: IArgon2Parameters;
  KDF: IPBKDF_Argon2;
begin
  Result := '';
  try
    PasswordBytes := TConverters.ConvertStringToBytes(Password, TEncoding.UTF8);
    SaltBytes := TConverters.ConvertStringToBytes(Salt, TEncoding.UTF8);

    ParamsBuilder := TArgon2idParametersBuilder.Builder();
    ParamsBuilder.WithIterations(Iterations);
    ParamsBuilder.WithMemoryAsKB(MemoryAsKB);
    ParamsBuilder.WithParallelism(Parallelism);
    ParamsBuilder.WithSalt(SaltBytes);

    Params := ParamsBuilder.Build();
    ParamsBuilder.Clear(); // Очищаем билдер

    KDF := TKDF.TPBKDF_Argon2.CreatePBKDF_Argon2(PasswordBytes, Params);
    try
      HashBytes := KDF.GetBytes(32); // 32-байтовый выходной хеш
      Result := TConverters.ConvertBytesToHexString(HashBytes, False); // Нижний регистр
    finally
      KDF.Clear();
    end;

    Params.Clear();
  except
    on E: Exception do
      WriteLn('[ERR] HashPasswordArgon2 failed: ', E.Message);
  end;
end;

end.
