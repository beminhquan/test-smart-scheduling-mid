import json
import sys
from bo_may_xep_lich.xu_ly_du_lieu import DuLieuDauVao
from bo_may_xep_lich.bo_may_xep_lich import BoMayXepLich

with open('payload_2026_11_02.json', 'r', encoding='utf-8') as f:
    payload = json.load(f)

du_lieu = DuLieuDauVao.parse_obj(payload)
engine = BoMayXepLich(du_lieu)
res = engine.giai()

print(f"Status: {res.status}")
print(f"Total assignments: {len(res.assignments)}")
print(f"Unassigned demands: {len(res.unassigned_demands)}")

# Group assignments by employee
emp_shifts = {}
emp_hours = {}
emp_roles = {}
for e in du_lieu.employees:
    emp_shifts[e.id] = 0
    emp_hours[e.id] = 0.0
    emp_roles[e.id] = (e.name, e.role_name, e.role_tag_id)

for a in res.assignments:
    emp_shifts[a.employee_id] += 1
    # Calculate duration
    for s in du_lieu.shifts:
        if s.id == a.shift_id:
            emp_hours[a.employee_id] += (s.duration_hours or 0.0)

# Print by role
by_role = {}
for emp_id, (name, role_name, r_id) in emp_roles.items():
    if r_id not in by_role:
        by_role[r_id] = []
    by_role[r_id].append((emp_id, name, emp_shifts[emp_id], emp_hours[emp_id]))

for r_id, emps in by_role.items():
    print(f"\n--- Role ID: {r_id} ---")
    for emp_id, name, shifts, hours in emps:
        print(f"  ID: {emp_id:2d} | Name: {name:20s} | Shifts: {shifts} | Hours: {hours:.1f}h")

