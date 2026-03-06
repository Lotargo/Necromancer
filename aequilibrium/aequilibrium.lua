-- Aequilibrium: Microservitium pro statera (Load Balancer) provisorum LLM
-- Lingua: LuaJIT (Omnia Latine scripta sunt)

local ffi = require("ffi")

-- Definitiones C pro socketis (raw TCP)
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

-- Tabularium (Database) ubi claves habitant
local TABULARIUM_CLAVES = "../tabularium/claves.txt"

-- Structurae Datorum (Data Structures)
local Provisores = {}
local NumerusProvisorum = 0
local IndexHodiernus = 1

-- Sessiones (Sessions) pro ReAct / CoT statera
local Sessiones = {}

-- Functio: Legere lineas ex fasciculo (Read lines from file)
local function LegereClaves()
    Provisores = {}
    NumerusProvisorum = 0

    local fasciculus = io.open(TABULARIUM_CLAVES, "r")
    if not fasciculus then
        print("[!] Error: Non possum aperire fasciculum " .. TABULARIUM_CLAVES)
        return
    end

    for linea in fasciculus:lines() do
        -- Format: PROVISOR|CLAVIS|URL|MODEL
        local provisor, clavis, url, model = linea:match("([^|]+)|([^|]+)|([^|]+)|([^|]+)")
        if provisor and clavis and url and model then
            NumerusProvisorum = NumerusProvisorum + 1
            Provisores[NumerusProvisorum] = {
                provisor = provisor,
                clavis = clavis,
                url = url,
                model = model
            }
            print("[+] Provisor inventus: " .. provisor .. " (" .. model .. ")")
        end
    end
    fasciculus:close()

    if NumerusProvisorum == 0 then
        print("[-] Nulli provisores inventi in " .. TABULARIUM_CLAVES)
    else
        print("[*] Summa provisorum: " .. NumerusProvisorum)
    end
end

-- Functio: Eligere provisorem pro sessione (Select provider for session)
local function EligereProvisorem(id_sessionis)
    if NumerusProvisorum == 0 then
        return nil, "Nullus provisor paratus est"
    end

    -- Si sessio iam habet provisorem, retine eum (pro ReAct / CoT contextu)
    if id_sessionis and id_sessionis ~= "" and id_sessionis ~= "default" then
        if Sessiones[id_sessionis] then
            return Sessiones[id_sessionis], nil
        end
    end

    -- Si non habet provisorem vel id_sessionis deest, elige proximum (Round-Robin)
    local electus = Provisores[IndexHodiernus]

    -- Incrementum et statera (Round-Robin)
    IndexHodiernus = IndexHodiernus + 1
    if IndexHodiernus > NumerusProvisorum then
        IndexHodiernus = 1
    end

    -- Serva electionem pro hac sessione
    if id_sessionis and id_sessionis ~= "" and id_sessionis ~= "default" then
        Sessiones[id_sessionis] = electus
    end

    return electus, nil
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
        if mandatum == "PETERE_CLAVEM" then
            -- Reload claves on every request to support hot-swapping
            LegereClaves()

            local provisor_electus, error_msg = EligereProvisorem(parametrum1)
            if provisor_electus then
                responsum = FormareResponsum(200, "Successus", provisor_electus.provisor, provisor_electus.clavis, provisor_electus.url, provisor_electus.model)
                print("[>] Sessio: " .. (parametrum1 or "ignota") .. " -> Electus: " .. provisor_electus.provisor)
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
print("------------------------------------------------")

LegereClaves()

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
