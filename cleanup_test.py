#!/usr/bin/env python3
import re

with open('tests/Feature/PatientProfileTest.php', 'r') as f:
    content = f.read()

# Remove booking gate debug test
content = re.sub(r"test\('booking gate debug'.*?\}\);\n\n", '', content, flags=re.DOTALL)

# Remove booking gate isolated test  
content = re.sub(r"test\('booking gate isolated.*?\}\);\n\n\n\n", '', content, flags=re.DOTALL)

# Remove dump lines from any remaining tests
content = content.replace("    dump('STATUS: ' . $resp->status());\n", '')
content = content.replace("    dump('BODY: ' . $resp->getContent());\n", '')

# Fix actingAs back to Sanctum::actingAs
content = content.replace("$this->actingAs($user, 'sanctum');", 'Sanctum::actingAs($user);')

with open('tests/Feature/PatientProfileTest.php', 'w') as f:
    f.write(content)

print('Done, lines: ' + str(content.count('\n')))
