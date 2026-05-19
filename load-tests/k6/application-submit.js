/**
 * Scenario C/D — Application submission (with and without documents)
 * 30 virtual users, 5 minutes
 *
 * Prerequisites:
 *   php artisan recruitment:seed-load-test --applicants=200 --vacancies=5 --applications=0
 *   Set VACANCY_ID env var to a valid open vacancy UUID.
 *   Set APPLICANT_TOKENS env var to a JSON array of [email, password] pairs.
 *
 * Run:
 *   k6 run -e BASE_URL=http://localhost:8000 \
 *           -e VACANCY_ID=<uuid> \
 *           load-tests/k6/application-submit.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';

const errorRate  = new Rate('errors');
const submitted  = new Counter('applications_submitted');
const duplicates = new Counter('duplicates_caught');

export const options = {
    scenarios: {
        submit: {
            executor: 'constant-vus',
            vus: 30,
            duration: '5m',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<2500'],
        errors: ['rate<0.01'],
        duplicates_caught: ['count==0'],
    },
};

const BASE_URL  = __ENV.BASE_URL  || 'http://localhost:8000';
const VACANCY_ID = __ENV.VACANCY_ID || '';

function getCsrfToken(res) {
    const match = res.body.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : '';
}

function login(email, password) {
    const page  = http.get(`${BASE_URL}/applicant/login`);
    const token = getCsrfToken(page);
    const jar   = http.cookieJar();

    const res = http.post(`${BASE_URL}/applicant/login`, {
        _token:   token,
        email:    email,
        password: password,
    }, {
        headers:   { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
        jar,
    });

    return { jar, ok: res.status === 200 };
}

export default function (__VU, __ITER) {
    if (!VACANCY_ID) {
        console.error('Set VACANCY_ID env var');
        errorRate.add(1);
        return;
    }

    // Each VU uses a distinct load-test applicant seeded beforehand.
    const email = `loadtest_applicant_${__VU}@testmail.invalid`;
    const { jar, ok: loggedIn } = login(email, 'Password123!');

    if (!loggedIn) {
        errorRate.add(1);
        sleep(2);
        return;
    }

    // Load the application create form
    const formRes = http.get(`${BASE_URL}/applicant/applications/${VACANCY_ID}/create`, {
        jar,
        redirects: 5,
    });

    const token = getCsrfToken(formRes);
    if (!token || formRes.status !== 200) {
        errorRate.add(1);
        sleep(1);
        return;
    }

    const submitRes = http.post(`${BASE_URL}/applicant/applications/${VACANCY_ID}`, {
        _token:          token,
        field_of_study:  'Computer Science',
        graduation_date: '2022-06-01',
        cgpa:            '3.50',
    }, {
        headers:   { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
        jar,
    });

    const isOk       = check(submitRes, { 'submit 200 or 302': (r) => r.status < 400 });
    const isDuplicate = submitRes.body.includes('already applied') || submitRes.body.includes('duplicate');

    if (isDuplicate) {
        duplicates.add(1);
    } else if (isOk) {
        submitted.add(1);
    } else {
        errorRate.add(1);
    }

    sleep(3);
}
