/**
 * Scenario B — Applicant registration
 * 50 virtual users, 5 minutes
 * Each VU registers a unique applicant with unique email/phone/national_id
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';
import { uuidv4 } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

const errorRate      = new Rate('errors');
const registrations  = new Counter('successful_registrations');

export const options = {
    scenarios: {
        register: {
            executor: 'constant-vus',
            vus: 50,
            duration: '5m',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        errors: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

function getCsrfToken(res) {
    const match = res.body.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : '';
}

export default function () {
    const uid  = uuidv4().replace(/-/g, '').substring(0, 10);
    const name = `LoadUser${uid}`;

    // 1. Load register page to get CSRF token
    let res = http.get(`${BASE_URL}/applicant/register`);
    const token = getCsrfToken(res);
    if (!token) {
        errorRate.add(1);
        sleep(1);
        return;
    }

    // 2. Submit registration
    res = http.post(`${BASE_URL}/applicant/register`, {
        _token:                  token,
        name:                    name,
        email:                   `loadtest_${uid}@testmail.invalid`,
        password:                'Password123!',
        password_confirmation:   'Password123!',
        first_name:              'Load',
        last_name:               `Test${uid}`,
        phone:                   `09${uid.substring(0, 8)}`,
        national_id:             `ID${uid.substring(0, 8)}`,
        gender:                  'male',
        disability_status:       '0',
    }, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 0,
    });

    const ok = check(res, {
        'registration redirects (302)': (r) => r.status === 302,
    });

    if (ok) registrations.add(1);
    errorRate.add(!ok);

    sleep(2);
}
