# Navis pro Daemonio (FreePascal)
ARG BASE_IMAGE=debian:bookworm-slim
FROM ${BASE_IMAGE}

ARG INSTALL_SYSTEM_DEPS=1

RUN if [ "$INSTALL_SYSTEM_DEPS" = "1" ]; then \
        apt-get update && apt-get install -y \
        fpc \
        libpq5 \
        libpq-dev \
        && rm -rf /var/lib/apt/lists/*; \
    fi

RUN rm -rf /app/daemonium

WORKDIR /app/daemonium

# Copy the pascal source code
COPY *.pas ./
COPY hashlib.cfg ./
COPY HashLib4Pascal/ ./HashLib4Pascal/

# Compile the daemonium
RUN fpc @hashlib.cfg daemonium.pas

# Expose the socket port
EXPOSE 8080

# Run the compiled binary
CMD ["./daemonium"]
