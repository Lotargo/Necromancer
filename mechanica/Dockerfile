# Dockerfile pro Mechanica (FreePascal Sandbox Runner)
ARG BASE_IMAGE=debian:bookworm-slim
FROM ${BASE_IMAGE}

ARG INSTALL_SYSTEM_DEPS=1

RUN if [ "$INSTALL_SYSTEM_DEPS" = "1" ]; then \
        apt-get update && apt-get install -y \
        fpc \
        && rm -rf /var/lib/apt/lists/*; \
    fi

RUN rm -rf /app/mechanica

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
