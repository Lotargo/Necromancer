import os

path = r'f:\lock-rep-stable-projects\Necromancer\interpres\fabulatio.php'
with open(path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Add missing brace. In Step 308, line 1446 was ' }' (closing the if at 1408).
# We need to insert a new line at index 1446 (which will become the new line 1447).
lines.insert(1446, "                                     }\n")

# Add state reset. In Step 329, line 1360 was ' fetch('.
# We insert at index 1359 (original line 1360).
lines.insert(1359, "            window.streamingState = null;\n")

with open(path, 'w', encoding='utf-8', newline='\n') as f:
    f.writelines(lines)
print("LINE-BASED PATCH SUCCESSFUL")
