# Navis pro Daemonio (FreePascal)
FROM public.ecr.aws/debian/debian:bullseye-slim

RUN apt-get update && apt-get install -y \
    fpc \
    libpq5 \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app/daemonium

# Copy the pascal source code
COPY *.pas ./

# Compile the daemonium
RUN fpc daemonium.pas

# Expose the socket port
EXPOSE 8080

# Run the compiled binary
CMD ["./daemonium"]
