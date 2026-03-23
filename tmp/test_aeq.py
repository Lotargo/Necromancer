import socket

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.connect(("127.0.0.1", 8081))
s.sendall(b"PETERE_CLAVEM|test_123\n")
resp = s.recv(4096).decode('utf-8')
s.close()

print("RAW Aeq Response:", repr(resp))
