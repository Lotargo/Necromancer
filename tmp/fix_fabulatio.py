import os

path = r'f:\lock-rep-stable-projects\Necromancer\interpres\fabulatio.php'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# 1. Fix missing brace
old_braces = """                                         }
                                 } catch(e) {}"""
new_braces = """                                         }
                                     }
                                 } catch(e) {}"""

if old_braces in text:
    text = text.replace(old_braces, new_braces)
    print("Fixed braces")
else:
    print("Braces NOT FOUND")

# 2. Reset streamingState before fetch
old_fetch = "fetch('api.php?action=send&'"
new_fetch = "window.streamingState = null; fetch('api.php?action=send&'"

if old_fetch in text and "window.streamingState = null;" not in text:
    text = text.replace(old_fetch, new_fetch)
    print("Added state reset")

with open(path, 'w', encoding='utf-8', newline='\n') as f:
    f.write(text)
print("PATCH COMPLETED")
