import re
import subprocess

with open('wp-content/plugins/tbp-actividades/includes/asientos/class-tbp-asientos-admin.php', 'r') as f:
    content = f.read()

# Extract script blocks
scripts = re.findall(r'<script>(.*?)</script>', content, re.DOTALL)

for i, script in enumerate(scripts):
    # Replace PHP tags with dummy JS values to simulate rendered output
    # For <?php if (...) : ?> and <?php endif; ?> and <?php else : ?> we remove them
    script_clean = re.sub(r'<\?php\s+if\s*\(.*?\)\s*:\s*\?>', '', script)
    script_clean = re.sub(r'<\?php\s+endif;\s*\?>', '', script_clean)
    script_clean = re.sub(r'<\?php\s+else\s*:\s*\?>', '', script_clean)
    
    # For <?php echo ... ; ?> we replace with 0 or '""' depending on context
    script_clean = re.sub(r'<\?php\s+echo\s+wp_json_encode.*?\?>', '[]', script_clean)
    script_clean = re.sub(r'<\?php\s+echo.*?_nonce.*?\?>', '"dummy_nonce"', script_clean)
    script_clean = re.sub(r'<\?php\s+echo.*?\?>', '0', script_clean)
    
    # Also handle short tags like <?= ?>
    script_clean = re.sub(r'<\?=.*?\?>', '0', script_clean)

    with open(f'temp_script_{i}.js', 'w') as temp:
        temp.write(script_clean)
        
    result = subprocess.run(['node', '-c', f'temp_script_{i}.js'], capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error in script {i}:")
        print(result.stderr)
    else:
        print(f"Script {i} syntax OK.")
