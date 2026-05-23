# Dockerfile pro Mechanica (FreePascal Sandbox Runner)
FROM public.ecr.aws/debian/debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    fpc \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app/mechanica

# Copy the Pascal source code
COPY mechanica.pas ./

# Compile mechanica
RUN fpc mechanica.pas

# Create sandbox directory
RUN mkdir -p sandbox

EXPOSE 8082

# Run the compiled daemon
CMD ["./mechanica"]
