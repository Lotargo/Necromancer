# Navis pro Daemonio (FreePascal)
FROM public.ecr.aws/debian/debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    fpc \
    libpq5 \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app/daemonium

# Copy the pascal source code
COPY *.pas ./
COPY hashlib.cfg ./
COPY HashLib4Pascal/ ./HashLib4Pascal/

# Install git and clone HashLib4Pascal if empty, otherwise we rely on the COPY
RUN apt-get update && apt-get install -y git && \
    rm -rf ./HashLib4Pascal && \
    git clone --branch v4.2 --depth 1 https://github.com/Xor-el/HashLib4Pascal.git ./HashLib4Pascal

# Compile the daemonium
RUN fpc @hashlib.cfg daemonium.pas

# Expose the socket port
EXPOSE 8080

# Run the compiled binary
CMD ["./daemonium"]
