import re
import os

with open('wp-content/plugins/tbp-actividades/includes/asientos/class-tbp-asientos-admin.php', 'r') as f:
    content = f.read()

# Extract script block
match = re.search(r'<script>\s*jQuery\(document\)\.ready\(function\(\$\)\s*\{(.*?)</script>', content, re.DOTALL)
if not match:
    print("Script block not found!")
    exit(1)

script = match.group(1)

# Replace PHP blocks
script = re.sub(r'<\?php\s+if\s*\(.*?\)\s*:\s*\?>', '', script)
script = re.sub(r'<\?php\s+endif;\s*\?>', '', script)
script = re.sub(r'<\?php\s+echo\s+wp_json_encode.*?\?>', '[]', script)
script = re.sub(r'<\?php\s+echo.*?_nonce.*?\?>', '"dummy_nonce"', script)
script = re.sub(r'<\?php\s+echo.*?\?>', '25', script)
script = re.sub(r'<\?=.*?\?>', '25', script)
script = re.sub(r'<\?php\s+echo\s+esc_js.*?\?>', 'grupo_test', script)

html = f"""
<!DOCTYPE html>
<html>
<head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<input type="hidden" id="event_id" value="1">
<input type="hidden" id="zonas_json" value="[]">
<div class="stage-item" data-stage="1">Tab 1</div>
<button id="btn_scan_event">Scan</button>
<script>
jQuery(document).ready(function($) {{
{script}
}});
</script>
</body>
</html>
"""

with open('test_browser.html', 'w') as f:
    f.write(html)

print("Created test_browser.html")
