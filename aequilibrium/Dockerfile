FROM alpine:3.19

# Installe LuaJIT
RUN apk add --no-cache luajit

WORKDIR /app/aequilibrium

COPY . .

# Expose the TCP port
EXPOSE 8081

# Start the LuaJIT server
CMD ["luajit", "aequilibrium.lua"]
