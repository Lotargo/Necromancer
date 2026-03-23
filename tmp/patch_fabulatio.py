import sys
import os

path = r'f:\lock-rep-stable-projects\Necromancer\interpres\fabulatio.php'
with open(path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = -1
for i, line in enumerate(lines):
    if '} else if (dataNode.choices && dataNode.choices[0].delta) {' in line:
        start_idx = i
        break

if start_idx != -1:
    end_idx = -1
    for i in range(start_idx, len(lines)):
        if '} catch(e) {}' in lines[i]:
            end_idx = i
            break
            
    if end_idx != -1:
        new_content = """                                     } else if (dataNode.choices && dataNode.choices[0].delta) {
                                         const delta = dataNode.choices[0].delta;
                                         if (delta.reasoning_content || delta.content) {
                                             if (!window.streamingState) {
                                                 window.streamingState = { reasoning: "", content: "", inThought: false, rid: 0 };
                                             }
                                             const s = window.streamingState;
                                             if (delta.reasoning_content) s.reasoning += delta.reasoning_content;
                                             if (delta.content) {
                                                 let c = delta.content;
                                                 if (c.includes("<thought>")) { s.inThought = true; c = c.replace("<thought>", ""); }
                                                 if (c.includes("</thought>")) { s.inThought = false; c = c.replace("</thought>", ""); }
                                                 if (s.inThought) s.reasoning += c; else s.content += c;
                                             }
                                             if (!s.rid) {
                                                 s.rid = requestAnimationFrame(() => {
                                                     if (!window.streamingState) return;
                                                     if (s.reasoning) {
                                                         if (!reasoningSpan) {
                                                             reasoningSpan = document.createElement('details');
                                                             reasoningSpan.className = 'reasoning-details';
                                                             reasoningSpan.innerHTML = '<summary>Cogitationes Oraculi...</summary><div class="reasoning-content"></div>';
                                                             chatEl.insertBefore(reasoningSpan, oraclePrefix);
                                                         }
                                                         const contentDiv = reasoningSpan.querySelector('.reasoning-content');
                                                         if (contentDiv) contentDiv.innerHTML = DOMPurify.sanitize(marked.parse(s.reasoning));
                                                     }
                                                     if (s.content) {
                                                         normalTextSpan.innerHTML = DOMPurify.sanitize(marked.parse(s.content));
                                                         renderMathInElement(normalTextSpan, {
                                                             delimiters: [
                                                                 {left: '$$', right: '$$', display: true}, {left: '\\\\[', right: '\\\\]', display: true},
                                                                 {left: '$', right: '$', display: false}, {left: '\\\\(', right: '\\\\)', display: false}
                                                             ], throwOnError: false
                                                         });
                                                     }
                                                     chatEl.scrollTop = chatEl.scrollHeight;
                                                     s.rid = 0;
                                                 });
                                             }
                                         }
"""
        lines[start_idx:end_idx] = [new_content]
        with open(path, 'w', encoding='utf-8', newline='\n') as f:
            f.writelines(lines)
        print("SUCCESS")
    else:
        print("END NOT FOUND")
else:
    print("START NOT FOUND")
