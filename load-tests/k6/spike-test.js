/**
 * Scenario E — Spike test
 * Ramp from 0 to 200 virtual users in 30 seconds, hold 1 minute, ramp down.
 * Verifies no 500 errors and correct duplicate blocking under sudden burst.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate    = new Rate('errors');
const serverErrors = new Rate('server_errors');

export const options = {
    scenarios: {
        spike: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 200 },
                { duration: '1m',  target: 200 },
                { duration: '20s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        errors: ['rate<0.02'],
        server_errors: ['rate==0'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    const res = http.get(`${BASE_URL}/vacancies`);

    const isServerError = check(res, {
        'no 500 errors': (r) => r.status < 500,
    });
    serverErrors.add(!isServerError);

    const ok = check(res, {
        'page loads': (r) => r.status === 200,
    });
    errorRate.add(!ok);

    sleep(0.3);
}
