ARG BASE_IMAGE=alpine:3.19
FROM ${BASE_IMAGE}

ARG INSTALL_SYSTEM_DEPS=1

# Installe LuaJIT
RUN if [ "$INSTALL_SYSTEM_DEPS" = "1" ]; then apk add --no-cache luajit; fi

RUN rm -rf /app/aequilibrium

WORKDIR /app/aequilibrium

COPY . .

# Expose the TCP port
EXPOSE 8081

# Start the LuaJIT server
CMD ["luajit", "aequilibrium.lua"]
