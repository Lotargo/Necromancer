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

# Compile the daemonium
RUN fpc @hashlib.cfg daemonium.pas

# Expose the socket port
EXPOSE 8080

# Run the compiled binary
CMD ["./daemonium"]
