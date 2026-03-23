import os

path = r'f:\lock-rep-stable-projects\Necromancer\aequilibrium\aequilibrium.lua'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# 1. Modify ColligereDataProvisorum
old_colligere_decl = """-- Functio: Colligere omnia data provisoris (Hot Reload / On the fly)
local function ColligereDataProvisorum()
    local provisores_parati = {}"""

new_colligere_decl = """local ProvisoresParatiCache = nil
local UltimaLectio = 0

-- Functio: Colligere omnia data provisoris (Hot Reload / On the fly)
local function ColligereDataProvisorum()
    local nunc = os.time()
    if ProvisoresParatiCache and (nunc - UltimaLectio < 60) then
        return ProvisoresParatiCache
    end

    local provisores_parati = {}"""

if old_colligere_decl in text:
    text = text.replace(old_colligere_decl, new_colligere_decl)
    print("PATCH 1 SUCCESS")
else:
    print("PATCH 1 FAILED")

old_colligere_ret = """    end

    return provisores_parati
end"""
new_colligere_ret = """    end

    ProvisoresParatiCache = provisores_parati
    UltimaLectio = nunc

    return provisores_parati
end"""

if old_colligere_ret in text:
    text = text.replace(old_colligere_ret, new_colligere_ret)
    print("PATCH 2 SUCCESS")
else:
    print("PATCH 2 FAILED")

old_warmup = """print("Aequilibrium audit in portu " .. PORTUS .. " ...")

-- Loop principalis (Main loop)"""

new_warmup = """print("Aequilibrium audit in portu " .. PORTUS .. " ...")

-- Warm up cache before answering requests
ColligereDataProvisorum()
print("Cache provisorum paratus est.")

-- Loop principalis (Main loop)"""

if old_warmup in text:
    text = text.replace(old_warmup, new_warmup)
    print("PATCH 3 SUCCESS")
else:
    print("PATCH 3 FAILED")

with open(path, 'w', encoding='utf-8', newline='\n') as f:
    f.write(text)
print("WRITE COMPLETE")
