import re
import os
import subprocess

with open('wp-content/plugins/tbp-actividades/includes/asientos/class-tbp-asientos-admin.php', 'r') as f:
    content = f.read()

# Extract script block strictly
match = re.search(r'<script>\s*(jQuery\(document\)\.ready\(function\(\$\)\s*\{.*?\}\);\s*)</script>', content, re.DOTALL)
if not match:
    print("Script not found")
    exit(1)

script = match.group(1)

# Remove all <?php ... ?> tags
script = re.sub(r'<\?php\s+if\s*\(.*?\)\s*:\s*\?>', '', script)
script = re.sub(r'<\?php\s+endif;\s*\?>', '', script)
script = re.sub(r'<\?php\s+echo\s+wp_json_encode.*?\?>', '[]', script)
script = re.sub(r'<\?php\s+echo\s+\(int\).*?\?>', '25', script)
script = re.sub(r'<\?php\s+echo.*?\?>', '"dummy"', script)

with open('pure_script.js', 'w') as f:
    f.write(script)

print("Saved to pure_script.js")
