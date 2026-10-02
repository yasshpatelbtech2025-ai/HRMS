/**
 * Attendance › Shift management. Each department rotates its employees
 * through the chosen shift parts, moving to the next part after
 * `iteration` working days; the weekly off is WO. Employees are staggered
 * so the parts are covered from day one. A ticked "Edit" freezes an
 * employee's days so they can be changed by hand.
 */
const WEEKDAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

export function layOut(group, entry, index, year, month, daysInMonth) {
    const parts = group.parts.length ? group.parts : ['GS'];
    const days = {};
    let worked = 0;

    for (let day = 1; day <= daysInMonth; day++) {
        const weekday = WEEKDAYS[new Date(year, month - 1, day).getDay()];
        if (weekday === entry.weeklyOff) {
            days[day] = 'WO';
            continue;
        }
        days[day] = parts[(Math.floor(worked / group.iteration) + index) % parts.length];
        worked++;
    }

    return days;
}

export function shiftRoster(config) {
    return {
        groups: config.groups,
        year: config.year,
        month: config.month,
        daysInMonth: config.daysInMonth,

        init() {
            this.groups.forEach((group) => this.refresh(group));
        },

        refresh(group) {
            group.entries.forEach((entry, index) => {
                if (!entry.manual) {
                    entry.days = layOut(group, entry, index, this.year, this.month, this.daysInMonth);
                }
            });
        },

        // Employees of the department not already on any roster this month.
        addable(group) {
            const taken = new Set(this.groups.flatMap((g) => g.entries.map((e) => e.uuid)));
            return group.available.filter((e) => !taken.has(e.uuid));
        },

        add(group, uuid) {
            const employee = group.available.find((e) => e.uuid === uuid);
            if (!employee) return;
            group.entries.push({ uuid: employee.uuid, label: employee.label, weeklyOff: employee.weeklyOff, manual: false, days: {} });
            this.refresh(group);
        },

        remove(group, index) {
            group.entries.splice(index, 1);
            this.refresh(group);
        },

        clear(group) {
            group.parts = [];
            group.entries = [];
        },

        toggleManual(group, entry) {
            if (!entry.manual) this.refresh(group);
        },

        codes(group) {
            return [...new Set([...group.parts, 'GS', 'WO'])];
        },

        get payload() {
            return JSON.stringify(this.groups.map((g) => ({
                id: g.id, parts: g.parts, iteration: Number(g.iteration),
                entries: g.entries.map((e) => ({ uuid: e.uuid, weeklyOff: e.weeklyOff, manual: e.manual, days: e.days })),
            })));
        },
    };
}
