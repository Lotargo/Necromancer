# Navis pro Daemonio (FreePascal)
FROM public.ecr.aws/debian/debian:bullseye-slim

RUN apt-get update && apt-get install -y \
    fpc \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app/daemonium

# Copy the pascal source code
COPY daemonium.pas .

# Compile the daemonium
RUN fpc daemonium.pas

# Expose the socket port
EXPOSE 8080

# Run the compiled binary
CMD ["./daemonium"]
