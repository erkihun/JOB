/**
 * Scenario A — Public vacancy browsing
 * 100 virtual users, 5 minutes
 * Threshold: p95 < 800ms, error rate < 1%
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate  = new Rate('errors');
const pageTime   = new Trend('page_response_time', true);

export const options = {
    scenarios: {
        browse: {
            executor: 'constant-vus',
            vus: 100,
            duration: '5m',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<800'],
        errors: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    // Public vacancy list
    let res = http.get(`${BASE_URL}/vacancies`, { tags: { name: 'vacancy_list' } });
    pageTime.add(res.timings.duration);
    let ok = check(res, { 'vacancy list 200': (r) => r.status === 200 });
    errorRate.add(!ok);

    sleep(0.5);

    // Home page
    res = http.get(`${BASE_URL}/`, { tags: { name: 'home' } });
    ok = check(res, { 'home 200': (r) => r.status === 200 });
    errorRate.add(!ok);

    sleep(0.5);

    // Announcements
    res = http.get(`${BASE_URL}/announcements`, { tags: { name: 'announcements' } });
    ok = check(res, { 'announcements 200': (r) => r.status === 200 });
    errorRate.add(!ok);

    sleep(1);
}
