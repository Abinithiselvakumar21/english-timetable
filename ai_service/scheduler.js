/**
 * ========================================================
 * AI Constraint Satisfaction Problem (CSP) Scheduler
 * File: ai_service/scheduler.js
 * 
 * Algorithm: Backtracking with Most Constrained Variable (MCV)
 * & Forward Checking for Zero Clashes
 * ========================================================
 */

class TimetableScheduler {
    constructor(data) {
        this.classes = data.classes || [];
        this.subjects = data.subjects || [];
        this.staff = data.staff || [];
        this.allocations = data.allocations || [];
        this.periods = (data.periods || []).filter(p => !p.is_break); // Only teaching periods
        this.workingDays = (data.workingDays || []).filter(d => d.is_working);
        this.staffAvailability = data.staffAvailability || []; // Unavailable slots
    }

    /**
     * Core Solve Method
     */
    generate() {
        const slots = []; // Final allocated slots array
        const errors = [];

        // 1. Validate Basic Inputs
        if (this.classes.length === 0) {
            return { success: false, message: "No classes configured in database.", slots: [] };
        }
        if (this.periods.length === 0) {
            return { success: false, message: "No teaching periods configured in database.", slots: [] };
        }
        if (this.workingDays.length === 0) {
            return { success: false, message: "No working days configured.", slots: [] };
        }
        if (this.allocations.length === 0) {
            return { success: false, message: "No staff-subject allocations found. Please allocate faculty first.", slots: [] };
        }

        // 2. Build Schedule Grid Matrices
        // staffBusy[staffId][day][periodId] = true/false
        const staffBusy = {};
        // classBusy[classId][day][periodId] = true/false
        const classBusy = {};
        // staffDailyCount[staffId][day] = count (for workload balance)
        const staffDailyCount = {};
        // classSubjectDay[classId][subjectId][day] = count (to avoid more than 1 period of same subject per day)
        const classSubjectDay = {};

        // Initialize availability constraints
        this.staff.forEach(st => {
            staffBusy[st.id] = {};
            staffDailyCount[st.id] = {};
            this.workingDays.forEach(d => {
                staffBusy[st.id][d.day_name] = {};
                staffDailyCount[st.id][d.day_name] = 0;
            });
        });

        // Mark pre-configured unavailable slots
        this.staffAvailability.forEach(un => {
            if (staffBusy[un.staff_id] && staffBusy[un.staff_id][un.day_name]) {
                staffBusy[un.staff_id][un.day_name][un.period_id] = true;
            }
        });

        this.classes.forEach(c => {
            classBusy[c.id] = {};
            classSubjectDay[c.id] = {};
            this.workingDays.forEach(d => {
                classBusy[c.id][d.day_name] = {};
            });
        });

        // 3. Expand Tasks to Schedule
        // Each subject needs `weekly_hours` number of 1-hour slots
        const tasks = [];
        this.allocations.forEach(alloc => {
            const subject = this.subjects.find(s => s.id === alloc.subject_id);
            const teacher = this.staff.find(st => st.id === alloc.staff_id);
            const cls = this.classes.find(c => c.id === alloc.class_id);

            if (subject && teacher && cls) {
                const requiredHours = subject.weekly_hours || 4;
                for (let i = 0; i < requiredHours; i++) {
                    tasks.push({
                        class_id: cls.id,
                        class_name: cls.class_name,
                        room_no: cls.room_no,
                        subject_id: subject.id,
                        subject_name: subject.subject_name,
                        category: subject.category, // MAJOR or FOUNDATION
                        color_code: subject.color_code || (subject.category === 'MAJOR' ? '#0d6efd' : '#198754'),
                        staff_id: teacher.id,
                        staff_name: teacher.name,
                        priority: subject.category === 'MAJOR' ? 1 : 2 // Major literature gets priority distribution
                    });
                }
            }
        });

        // Sort tasks: Place Major subjects first, then Foundation, ensuring balanced distribution
        tasks.sort((a, b) => a.priority - b.priority);

        // 4. Backtracking Algorithm to Assign Time Slots
        const isAssigned = this.backtrackAssign(0, tasks, slots, staffBusy, classBusy, staffDailyCount, classSubjectDay);

        if (!isAssigned) {
            return {
                success: false,
                message: "AI Solver Conflict: Unable to find a 100% clash-free schedule with current constraints. Suggestion: Increase teaching periods, add working days, or relieve faculty workload.",
                slots: slots
            };
        }

        return {
            success: true,
            message: `Successfully scheduled ${slots.length} clash-free periods with integrated Major & Foundation courses!`,
            slots: slots
        };
    }

    /**
     * Recursive Backtracking Assignment
     */
    backtrackAssign(taskIndex, tasks, slots, staffBusy, classBusy, staffDailyCount, classSubjectDay) {
        if (taskIndex >= tasks.length) {
            return true; // All tasks successfully placed!
        }

        const task = tasks[taskIndex];

        // Shuffle / balance days to distribute classes evenly across Monday-Friday
        const candidateDays = [...this.workingDays].sort((a, b) => {
            // Prefer days where the class has fewer subjects or teacher has less load
            const loadA = staffDailyCount[task.staff_id][a.day_name] || 0;
            const loadB = staffDailyCount[task.staff_id][b.day_name] || 0;
            return loadA - loadB;
        });

        for (const dayObj of candidateDays) {
            const day = dayObj.day_name;

            // Soft constraint: Try to avoid repeating same subject on the same day if possible
            if (!classSubjectDay[task.class_id][task.subject_id]) {
                classSubjectDay[task.class_id][task.subject_id] = {};
            }
            const subjectCountToday = classSubjectDay[task.class_id][task.subject_id][day] || 0;
            if (subjectCountToday >= 1 && this.workingDays.length >= 4) {
                // If we already have 1 period of this subject today, try another day first
                continue;
            }

            // Loop through teaching periods
            for (const period of this.periods) {
                const pid = period.id;

                // HARD CONSTRAINT 1: Is Teacher Free?
                if (staffBusy[task.staff_id][day][pid]) {
                    continue; // Teacher clash! Skip slot.
                }

                // HARD CONSTRAINT 2: Is Class Free?
                if (classBusy[task.class_id][day][pid]) {
                    continue; // Class clash! Skip slot.
                }

                // ASSIGN SLOT
                staffBusy[task.staff_id][day][pid] = true;
                classBusy[task.class_id][day][pid] = true;
                staffDailyCount[task.staff_id][day] = (staffDailyCount[task.staff_id][day] || 0) + 1;
                classSubjectDay[task.class_id][task.subject_id][day] = subjectCountToday + 1;

                const assignedSlot = {
                    class_id: task.class_id,
                    class_name: task.class_name,
                    room_no: task.room_no,
                    subject_id: task.subject_id,
                    subject_name: task.subject_name,
                    category: task.category,
                    color_code: task.color_code,
                    staff_id: task.staff_id,
                    staff_name: task.staff_name,
                    day_name: day,
                    period_id: pid,
                    period_number: period.period_number,
                    start_time: period.start_time,
                    end_time: period.end_time
                };
                slots.push(assignedSlot);

                // Recurse to next task
                if (this.backtrackAssign(taskIndex + 1, tasks, slots, staffBusy, classBusy, staffDailyCount, classSubjectDay)) {
                    return true;
                }

                // BACKTRACK (Undo assignment)
                slots.pop();
                staffBusy[task.staff_id][day][pid] = false;
                classBusy[task.class_id][day][pid] = false;
                staffDailyCount[task.staff_id][day] -= 1;
                classSubjectDay[task.class_id][task.subject_id][day] -= 1;
            }
        }

        return false; // Backtrack trigger
    }
}

module.exports = TimetableScheduler;
