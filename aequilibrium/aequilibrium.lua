-- Aequilibrium: Microservitium pro statera (Load Balancer) provisorum LLM
-- Lingua: LuaJIT (Omnia Latine scripta sunt)

local ffi = require("ffi")

-- Definitiones C pro socketis (raw TCP) et fasciculis
ffi.cdef[[
    typedef int socklen_t;
    typedef unsigned short sa_family_t;
    typedef uint16_t in_port_t;
    typedef uint32_t in_addr_t;

    struct in_addr {
        in_addr_t s_addr;
    };

    struct sockaddr_in {
        sa_family_t sin_family;
        in_port_t sin_port;
        struct in_addr sin_addr;
        char sin_zero[8];
    };

    struct sockaddr {
        sa_family_t sa_family;
        char sa_data[14];
    };

    int socket(int domain, int type, int protocol);
    int bind(int sockfd, const struct sockaddr *addr, socklen_t addrlen);
    int listen(int sockfd, int backlog);
    int accept(int sockfd, struct sockaddr *addr, socklen_t *addrlen);
    ssize_t recv(int sockfd, void *buf, size_t len, int flags);
    ssize_t send(int sockfd, const void *buf, size_t len, int flags);
    int close(int fd);
    int setsockopt(int sockfd, int level, int optname, const void *optval, socklen_t optlen);

    uint16_t htons(uint16_t hostshort);
    uint32_t htonl(uint32_t hostlong);
]]

local AF_INET = 2
local SOCK_STREAM = 1
local SOL_SOCKET = 1
local SO_REUSEADDR = 2
local INADDR_ANY = 0
local PORTUS = 8081

-- Tabularium (Database) ubi provisores habitant
local VIA_PROVISORUM = "../tabularium/provisores/"
local NOMINA_PROVISORUM = {"gemini", "groq", "cerebras", "sambanova"}

-- Structurae Datorum (Data Structures)
-- Indices pro Round-Robin (Servantur in memoria perenniter)
local IndexProvisor = 1
local IndicesClavium = {}
local IndicesModelorum = {}

for _, nomen in ipairs(NOMINA_PROVISORUM) do
    IndicesClavium[nomen] = 1
    IndicesModelorum[nomen] = 1
end

-- Sessiones (Sessions) pro ReAct / CoT statera
local Sessiones = {}

-- Functio: Legere lineas ex fasciculo (Read lines from file)
local function LegereFasciculum(via)
    local lineae = {}
    local fasciculus = io.open(via, "r")
    if not fasciculus then
        return lineae
    end
    for linea in fasciculus:lines() do
        -- Remove spaces/newlines
        linea = linea:gsub("^%s*(.-)%s*$", "%1")
        if linea ~= "" then
            table.insert(lineae, linea)
        end
    end
    fasciculus:close()
    return lineae
end

-- Functio: Legere unam lineam ex fasciculo
local function LegereUnamLineam(via)
    local lineae = LegereFasciculum(via)
    if #lineae > 0 then
        return lineae[1]
    end
    return nil
end

-- Functio: Colligere omnia data provisoris (Hot Reload / On the fly)
local function ColligereDataProvisorum()
    local provisores_parati = {}

    for _, nomen in ipairs(NOMINA_PROVISORUM) do
        local via = VIA_PROVISORUM .. nomen .. "/"

        local claves = LegereFasciculum(via .. "claves.txt")
        local modela = LegereFasciculum(via .. "modela.txt")
        local url = LegereUnamLineam(via .. "url.txt")

        if #claves > 0 and #modela > 0 and url then
            table.insert(provisores_parati, {
                nomen = nomen,
                claves = claves,
                modela = modela,
                url = url
            })
        end
    end

    return provisores_parati
end

-- Functio: Eligere proximam clavem et modelum pro provisore (Round-Robin internum)
local function EligereInterna(provisor_data)
    local nomen = provisor_data.nomen

    -- Eligere clavem
    local idx_clavis = IndicesClavium[nomen]
    if idx_clavis > #provisor_data.claves then
        idx_clavis = 1
    end
    local electa_clavis = provisor_data.claves[idx_clavis]
    IndicesClavium[nomen] = idx_clavis + 1

    -- Eligere modelum
    local idx_modelum = IndicesModelorum[nomen]
    if idx_modelum > #provisor_data.modela then
        idx_modelum = 1
    end
    local electum_modelum = provisor_data.modela[idx_modelum]
    IndicesModelorum[nomen] = idx_modelum + 1

    return electa_clavis, electum_modelum
end

-- Functio: Eligere provisorem pro sessione (Select provider for session)
local function EligereProvisorem(id_sessionis)
    local provisores_parati = ColligereDataProvisorum()
    local numerus = #provisores_parati

    if numerus == 0 then
        return nil, nil, nil, nil, "Nullus provisor cum clavibus et modelis paratus est"
    end

    -- Si sessio iam habet provisorem, retine eum (pro ReAct / CoT contextu)
    if id_sessionis and id_sessionis ~= "" and id_sessionis ~= "default" then
        if Sessiones[id_sessionis] then
            local s = Sessiones[id_sessionis]
            -- In sessione retinemus eundem provisorem, eandem clavem et idem modelum
            return s.nomen, s.clavis, s.url, s.modelum, nil
        end
    end

    -- Si non habet provisorem vel id_sessionis deest, elige proximum (Round-Robin inter provisores)
    if IndexProvisor > numerus then
        IndexProvisor = 1
    end

    local provisor_electus = provisores_parati[IndexProvisor]

    if not provisor_electus then
        IndexProvisor = 1
        provisor_electus = provisores_parati[IndexProvisor]
    end

    IndexProvisor = IndexProvisor + 1

    -- Eligere clavem et modelum internum pro hoc provisore
    local electa_clavis, electum_modelum = EligereInterna(provisor_electus)

    -- Serva electionem pro hac sessione
    if id_sessionis and id_sessionis ~= "" and id_sessionis ~= "default" then
        Sessiones[id_sessionis] = {
            nomen = provisor_electus.nomen,
            clavis = electa_clavis,
            url = provisor_electus.url,
            modelum = electum_modelum
        }
    end

    return provisor_electus.nomen, electa_clavis, provisor_electus.url, electum_modelum, nil
end

-- Functio: Formare responsum
local function FormareResponsum(codex, nuntius, provisor, clavis, url, model)
    if codex == 200 then
        return string.format("200|%s|%s|%s|%s|%s\n", nuntius, provisor, clavis, url, model)
    else
        return string.format("%d|%s||||\n", codex, nuntius)
    end
end

-- Functio: Tractare clientem (Handle client connection)
local function TractareClientem(cliens_sock)
    local buffer = ffi.new("char[1024]")
    local bytes_read = ffi.C.recv(cliens_sock, buffer, 1023, 0)

    if bytes_read > 0 then
        local linea_data = ffi.string(buffer, bytes_read)
        -- Remove whitespace and newline
        linea_data = linea_data:gsub("^%s*(.-)%s*$", "%1")

        -- Mandatum: PETERE_CLAVEM|ID_SESSIONIS
        local mandatum, parametrum1 = linea_data:match("([^|]+)|?([^|]*)")

        local responsum = ""
        if mandatum == "PURGARE_SESSIONEM" then
            Sessiones[parametrum1] = nil
            responsum = FormareResponsum(200, "Sessio purgata est", "", "", "", "")
            print("[>] Sessio purgata: " .. (parametrum1 or "ignota"))
        elseif mandatum == "PETERE_CLAVEM" then

            local nomen, clavis, url, modelum, error_msg = EligereProvisorem(parametrum1)

            if nomen then
                responsum = FormareResponsum(200, "Successus", nomen, clavis, url, modelum)
                print("[>] Sessio: " .. (parametrum1 or "ignota") .. " -> Electus: " .. nomen .. " [" .. modelum .. "] (Clavis rotata)")
            else
                responsum = FormareResponsum(500, error_msg, "", "", "", "")
                print("[!] Error: " .. error_msg)
            end
        else
            responsum = FormareResponsum(400, "Mandatum incognitum", "", "", "", "")
        end

        ffi.C.send(cliens_sock, responsum, #responsum, 0)
    end

    ffi.C.close(cliens_sock)
end

-- Initium Programmatis (Main)
print("------------------------------------------------")
print(" [!] AEQUILIBRIUM PROBUTUS EST / BALANCE AWAKENED")
print(" [!] Machina scripta in LuaJIT, omnia Latine.")
print(" [!] Legere claves et modela 'in the fly' (Vividus).")
print("------------------------------------------------")

local servus_sock = ffi.C.socket(AF_INET, SOCK_STREAM, 0)
if servus_sock < 0 then
    print("Error: Non potest creare socketum.")
    os.exit(1)
end

local optval = ffi.new("int[1]", 1)
ffi.C.setsockopt(servus_sock, SOL_SOCKET, SO_REUSEADDR, optval, ffi.sizeof("int"))

local adres = ffi.new("struct sockaddr_in")
adres.sin_family = AF_INET
adres.sin_port = ffi.C.htons(PORTUS)
adres.sin_addr.s_addr = ffi.C.htonl(INADDR_ANY)

if ffi.C.bind(servus_sock, ffi.cast("struct sockaddr *", adres), ffi.sizeof("struct sockaddr_in")) < 0 then
    print("Error: Non potest ligare socketum (bind).")
    os.exit(1)
end

if ffi.C.listen(servus_sock, 128) < 0 then
    print("Error: Non potest audire (listen).")
    os.exit(1)
end

print("Aequilibrium audit in portu " .. PORTUS .. " ...")

-- Loop principalis (Main loop)
while true do
    local cliens_sock = ffi.C.accept(servus_sock, nil, nil)
    if cliens_sock >= 0 then
        TractareClientem(cliens_sock)
    end
end

ffi.C.close(servus_sock)
