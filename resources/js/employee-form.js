/**
 * Alpine components for the Add / Edit employee form.
 *
 * employeeForm: company-dependent pickers, address copying, experience rows
 * and the salary structure with its statutory deduction preview.
 * photoCapture: choose or take the profile photo, compressed in the browser.
 */

// Gross elements a "New Structure" company (Configs › Payroll) may use.
const NEW_STRUCTURE_ELEMENTS = ['basic', 'allowance', 'bonus'];

const ADDRESS_FIELDS = ['address_line_1', 'address_line_2', 'village', 'district', 'taluka', 'state'];

const round2 = (value) => Math.round(value * 100) / 100;
const num = (value) => parseFloat(value) || 0;

export function employeeForm(config) {
    return {
        ...config.state,
        lookups: config.lookups,
        grossLabels: config.grossLabels,
        deductionLabels: config.deductionLabels,
        grossSalaryElements: config.grossSalaryElements,

        get departments() {
            return this.lookups.departments.filter((d) => String(d.company_id) === String(this.company));
        },
        get subDepartments() {
            return this.lookups.subDepartments.filter((s) => String(s.department_id) === String(this.department));
        },
        get positions() {
            return this.lookups.positions.filter((p) => String(p.company_id) === String(this.company));
        },
        get shifts() {
            return this.lookups.shifts.filter((s) => String(s.company_id) === String(this.company));
        },
        get officers() {
            return this.lookups.officers.filter((o) => String(o.company_id) === String(this.company));
        },

        // Picking another company or department clears choices that no longer belong to it.
        companyChanged() {
            if (!this.departments.some((d) => String(d.id) === String(this.department))) this.department = '';
            if (!this.positions.some((p) => String(p.id) === String(this.position))) this.position = '';
            if (!this.officers.some((o) => String(o.id) === String(this.officer))) this.officer = '';
            if (!this.shifts.some((s) => String(s.id) === String(this.shift))) this.shift = '';
            this.departmentChanged();
            if (this.newStructure) {
                this.grossShown.filter((e) => !NEW_STRUCTURE_ELEMENTS.includes(e)).forEach((e) => this.toggleElement('grossShown', e));
            }
        },
        departmentChanged() {
            if (!this.subDepartments.some((s) => String(s.id) === String(this.subDepartment))) this.subDepartment = '';
        },

        init() {
            // "Present Address Is Permanent Address" keeps the permanent address in step.
            this.$watch('addresses.present', () => this.permanentSame && this.copyAddress('present', 'permanent'), { deep: true });
            this.$watch('permanentSame', (same) => same && this.copyAddress('present', 'permanent'));
        },

        copyAddress(from, to) {
            ADDRESS_FIELDS.forEach((field) => (this.addresses[to][field] = this.addresses[from][field] ?? ''));
        },

        // "Nominee address is employee address" copies once, as in the old system.
        copyNomineeAddresses(checked) {
            if (!checked) return;
            this.copyAddress('present', 'nominee_current');
            this.copyAddress('permanent', 'nominee_permanent');
        },

        addExperience() {
            this.experiences.push({ company_name: '', designation: '', salary: '', total_years_worked: '', reason_of_leaving: '' });
        },
        removeExperience(index) {
            this.experiences.splice(index, 1);
        },

        // Salary structure ------------------------------------------------

        get newStructure() {
            return this.lookups.companies.find((c) => String(c.id) === String(this.company))?.structure === 'new';
        },
        offered(element) {
            return !this.newStructure || NEW_STRUCTURE_ELEMENTS.includes(element) || element in this.deductionLabels;
        },
        // New Structure: basic must be at least half of gross, i.e. not below the allowance.
        get basicTooLow() {
            return this.newStructure && num(this.salary.basic?.monthly) < num(this.salary.allowance?.monthly);
        },

        isShown(element) {
            return element === 'basic' || this.grossShown.includes(element) || this.deductionsShown.includes(element);
        },
        toggleElement(list, element) {
            const shown = this[list];
            const index = shown.indexOf(element);
            if (index === -1) {
                shown.push(element);
            } else {
                shown.splice(index, 1);
                this.salary[element] = { monthly: '', yearly: '' };
            }
        },
        monthlyChanged(element) {
            if (element === 'bonus') return; // a percentage of basic
            const monthly = this.salary[element].monthly;
            this.salary[element].yearly = monthly === '' ? '' : round2(num(monthly) * 12);
        },
        yearlyChanged(element) {
            const yearly = this.salary[element].yearly;
            this.salary[element].monthly = yearly === '' ? '' : round2(num(yearly) / 12);
        },

        get grossMonthly() {
            return this.grossSalaryElements.reduce((sum, element) => sum + num(this.salary[element]?.monthly), 0);
        },

        /**
         * Employee-side statutory deductions, as Payroll works them out: PF
         * 12% of basic when basic is ₹15,000 or less, for organisations over
         * 20; ESI 0.75% of gross when gross is ₹21,000 or less, organisations
         * over 10; Prof Tax ₹200 when gross is ₹12,000 or more, organisations
         * over 20; LWF ₹6 in June and December, organisations over 10. Daily
         * and hourly rates are converted to a month of 30 days (8 hours a day
         * for hourly).
         */
        get statutory() {
            const company = this.lookups.companies.find((c) => String(c.id) === String(this.company));
            if (!company) return null;

            const size = company.employees_count + 1;
            let basis;
            let gross;
            if (this.payFrequency === 'daily') {
                basis = gross = num(this.dailyRate) * 30;
            } else if (this.payFrequency === 'hourly') {
                basis = gross = num(this.hourlyRate) * 8 * 30;
            } else {
                basis = num(this.salary.basic?.monthly);
                gross = this.grossMonthly;
            }

            const pf = size > 20 && basis <= 15000 ? Math.round(basis * 0.12) : 0;
            const esi = size > 10 && gross <= 21000 ? Math.ceil(gross * 0.0075) : 0;
            const pt = size > 20 && gross >= 12000 ? 200 : 0;
            const lwf = size > 10 ? 12 : 0; // ₹6 in June and December

            return [
                { label: 'PF', monthly: pf, yearly: pf * 12 },
                { label: 'Employee State Insurance (ESI)', monthly: esi, yearly: esi * 12 },
                { label: 'PROF TAX', monthly: pt, yearly: pt * 12 },
                { label: 'LABOUR WELFARE FUND', monthly: lwf / 12, yearly: lwf },
            ];
        },

        money(value) {
            return round2(value).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}

/**
 * Photo picker with a camera option. Whatever is chosen or captured is
 * resized and re-encoded as JPEG until it fits in 30 KB, then sent as a
 * data: URL in the hidden photo_data field.
 */
export function photoCapture(existingUrl) {
    const MAX_BYTES = 30 * 1024;

    return {
        preview: existingUrl,
        data: '',
        info: '',
        stream: null,
        cameras: [],
        cameraIndex: 0,
        error: '',

        async chooseFile(event) {
            this.stopCamera();
            const file = event.target.files?.[0];
            if (!file) return;
            const image = await createImageBitmap(file);
            this.store(this.compress(image, image.width, image.height));
            event.target.value = '';
        },

        async openCamera() {
            this.error = '';
            try {
                if (!this.cameras.length) {
                    const probe = await navigator.mediaDevices.getUserMedia({ video: true });
                    probe.getTracks().forEach((track) => track.stop());
                    this.cameras = (await navigator.mediaDevices.enumerateDevices()).filter((d) => d.kind === 'videoinput');
                }
                if (!this.cameras.length) {
                    this.error = 'No camera found. Connect a camera or choose a file instead.';
                    return;
                }
                await this.startCamera();
            } catch (e) {
                this.error = `Unable to use the camera: ${e.message}`;
            }
        },

        async startCamera() {
            this.stopCamera();
            const camera = this.cameras[this.cameraIndex] ?? this.cameras[0];
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { deviceId: { exact: camera.deviceId }, width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            this.$refs.video.srcObject = this.stream;
        },

        capture() {
            const video = this.$refs.video;
            this.store(this.compress(video, video.videoWidth || 640, video.videoHeight || 480));
            this.stopCamera();
        },

        stopCamera() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
        },

        clear() {
            this.stopCamera();
            this.data = '';
            this.preview = existingUrl;
            this.info = '';
        },

        compress(source, width, height) {
            const canvas = document.createElement('canvas');
            let scale = Math.min(1, 480 / Math.max(width, height));
            let quality = 0.8;
            let url;
            do {
                canvas.width = Math.round(width * scale);
                canvas.height = Math.round(height * scale);
                canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
                url = canvas.toDataURL('image/jpeg', quality);
                if (quality > 0.4) quality -= 0.1;
                else scale *= 0.8;
            } while (this.bytes(url) > MAX_BYTES && canvas.width > 80);
            return url;
        },

        bytes(dataUrl) {
            return Math.ceil(((dataUrl.length - dataUrl.indexOf(',') - 1) * 3) / 4);
        },

        store(url) {
            this.data = url;
            this.preview = url;
            this.info = `${Math.round(this.bytes(url) / 1024)} KB JPEG`;
        },
    };
}
