<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Institution;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyAnnouncement;
use App\Models\VacancyDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Institutions ──────────────────────────────────────────────────
        $institutions = [
            [
                'name'       => 'Ministry of Education',
                'short_name' => 'MoE',
                'code'       => 'MOE',
                'type'       => 'Government',
                'email'      => 'hr@moe.gov.et',
                'phone'      => '+251111560111',
                'address'    => 'Arat Kilo, Addis Ababa',
                'latitude'   => 9.0249,
                'longitude'  => 38.7631,
                'status'     => 'active',
            ],
            [
                'name'       => 'Ministry of Health',
                'short_name' => 'MoH',
                'code'       => 'MOH',
                'type'       => 'Government',
                'email'      => 'hr@moh.gov.et',
                'phone'      => '+251111553517',
                'address'    => 'Kazanchis, Addis Ababa',
                'latitude'   => 9.0105,
                'longitude'  => 38.7614,
                'status'     => 'active',
            ],
            [
                'name'       => 'Addis Ababa City Administration',
                'short_name' => 'AACA',
                'code'       => 'AACA',
                'type'       => 'Government',
                'email'      => 'jobs@aaca.gov.et',
                'phone'      => '+251114428826',
                'address'    => 'Churchill Avenue, Addis Ababa',
                'latitude'   => 9.0287,
                'longitude'  => 38.7468,
                'status'     => 'active',
            ],
            [
                'name'       => 'Ethiopian Electric Power',
                'short_name' => 'EEP',
                'code'       => 'EEP',
                'type'       => 'Public Enterprise',
                'email'      => 'recruitment@eep.gov.et',
                'phone'      => '+251116620022',
                'address'    => 'Mexico Square, Addis Ababa',
                'latitude'   => 9.0153,
                'longitude'  => 38.7536,
                'status'     => 'active',
            ],
            [
                'name'       => 'Addis Ababa University',
                'short_name' => 'AAU',
                'code'       => 'AAU',
                'type'       => 'University',
                'email'      => 'hr@aau.edu.et',
                'phone'      => '+251111239753',
                'address'    => 'Sidist Kilo, Addis Ababa',
                'latitude'   => 9.0378,
                'longitude'  => 38.7620,
                'status'     => 'active',
            ],
        ];

        $createdInstitutions = [];
        foreach ($institutions as $data) {
            $createdInstitutions[] = Institution::firstOrCreate(['code' => $data['code']], $data);
        }

        // ── 2. Admin user (for created_by) ───────────────────────────────────
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        if (! $admin) {
            $admin = User::factory()->admin()->create([
                'name'  => 'Sample Admin',
                'email' => 'sampleadmin@jobs.local',
            ]);
        }

        // ── 3. Vacancies ─────────────────────────────────────────────────────
        $vacancyData = [
            // MoE vacancies
            [
                'institution' => 'MOE',
                'code'        => 'MOE-2026-001',
                'title'       => ['en' => 'Senior Education Officer', 'am' => 'ከፍተኛ የትምህርት ኦፊሰር'],
                'department'  => 'Curriculum & Standards',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
                'positions'   => 3,
                'salary'      => 'Grade 8',
                'field'       => 'Education',
                'experience'  => 5,
                'description' => ['en' => 'The Ministry of Education is seeking a qualified Senior Education Officer to lead curriculum development initiatives and coordinate with regional education bureaus.', 'am' => 'የትምህርት ሚኒስቴር ሥርዓተ ትምህርት ልማት ተነሳሽነቶችን ለመምራት እና ከክልል ትምህርት ቢሮዎች ጋር ለማስተባበር ብቁ ከፍተኛ የትምህርት ኦፊሰር እየፈለገ ነው።'],
                'requirements' => ['en' => "• Master's degree in Education or related field\n• Minimum 5 years experience in education management\n• Strong analytical and communication skills\n• Proficiency in English and Amharic", 'am' => "• በትምህርት ወይም በተዛማጅ ዘርፍ የማስተርስ ዲግሪ\n• ቢያንስ 5 ዓመት በትምህርት አስተዳደር ልምድ\n• ጠንካራ የትንታኔ እና የግንኙነት ክህሎቶች"],
                'opening'     => now()->subDays(5)->toDateString(),
                'closing'     => now()->addDays(25)->toDateString(),
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'Educational Certificate', 'required' => true],
                    ['name' => 'Work Experience Letter', 'required' => true],
                    ['name' => 'National ID', 'required' => true],
                ],
            ],
            [
                'institution' => 'MOE',
                'code'        => 'MOE-2026-002',
                'title'       => ['en' => 'IT Systems Administrator', 'am' => 'የአይቲ ስርዓቶች አስተዳዳሪ'],
                'department'  => 'ICT Directorate',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
                'positions'   => 2,
                'salary'      => 'Grade 7',
                'field'       => 'Computer Science',
                'experience'  => 3,
                'description' => ['en' => 'Responsible for maintaining and administering the Ministry\'s IT infrastructure, network systems and data security.', 'am' => 'የሚኒስቴሩን የአይቲ መሠረተ ልማት፣ የኔትወርክ ስርዓቶች እና የውሂብ ደህንነት ለመጠበቅ እና ለማስተዳደር ኃላፊነት አለበት።'],
                'requirements' => ['en' => "• BSc in Computer Science or IT\n• 3+ years system administration experience\n• Knowledge of Linux/Windows server environments\n• Networking certifications preferred", 'am' => "• በኮምፒዩተር ሳይንስ ወይም አይቲ የቢ.ኤስ.ሲ ዲግሪ\n• 3+ ዓመት የስርዓት አስተዳደር ልምድ"],
                'opening'     => now()->subDays(2)->toDateString(),
                'closing'     => now()->addDays(28)->toDateString(),
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'Educational Certificate', 'required' => true],
                    ['name' => 'Work Experience Letter', 'required' => true],
                ],
            ],
            // MoH vacancy
            [
                'institution' => 'MOH',
                'code'        => 'MOH-2026-001',
                'title'       => ['en' => 'Public Health Specialist', 'am' => 'የሕዝብ ጤና ስፔሻሊስት'],
                'department'  => 'Disease Prevention & Control',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
                'positions'   => 5,
                'salary'      => 'Grade 9',
                'field'       => 'Public Health',
                'experience'  => 4,
                'description' => ['en' => 'The Ministry of Health is recruiting Public Health Specialists to strengthen disease prevention programs and lead community health initiatives across Ethiopia.', 'am' => 'የጤና ሚኒስቴር የበሽታ መከላከያ ፕሮግራሞችን ለማጠናከር የሕዝብ ጤና ስፔሻሊስቶችን እየቀጠረ ነው።'],
                'requirements' => ['en' => "• MPH or equivalent graduate degree\n• 4+ years field experience in public health\n• Experience with disease surveillance systems\n• Excellent report writing skills", 'am' => "• MPH ወይም ተመሳሳይ ድህረ ምረቃ ዲግሪ\n• 4+ ዓመት በሕዝብ ጤና ልምድ"],
                'opening'     => now()->subDays(3)->toDateString(),
                'closing'     => now()->addDays(20)->toDateString(),
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'Educational Certificate', 'required' => true],
                    ['name' => 'Work Experience Letter', 'required' => true],
                    ['name' => 'Medical License', 'required' => false],
                ],
            ],
            // AACA vacancy
            [
                'institution' => 'AACA',
                'code'        => 'AACA-2026-001',
                'title'       => ['en' => 'Urban Planning Engineer', 'am' => 'የከተማ ፕላን መሐንዲስ'],
                'department'  => 'Urban Development',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
                'positions'   => 4,
                'salary'      => 'Grade 8',
                'field'       => 'Civil Engineering / Urban Planning',
                'experience'  => 5,
                'description' => ['en' => 'Join the Addis Ababa City Administration team as an Urban Planning Engineer to contribute to the sustainable development of the capital city.', 'am' => 'የከተማ ፕላን መሐንዲስ ሆነው ወደ አዲስ አበባ ከተማ አስተዳደር ቡድን ይቀላቀሉ።'],
                'requirements' => ['en' => "• BSc or MSc in Civil Engineering or Urban Planning\n• 5 years relevant experience\n• AutoCAD and GIS proficiency\n• Ethiopian Engineering registration preferred", 'am' => "• በሲቪል ምህንድስና ወይም የከተማ ፕላን ዲግሪ\n• 5 ዓመት ተዛማጅ ልምድ"],
                'opening'     => now()->subDays(7)->toDateString(),
                'closing'     => now()->addDays(5)->toDateString(), // urgent
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'Educational Certificate', 'required' => true],
                    ['name' => 'Work Experience Letter', 'required' => true],
                    ['name' => 'Engineering License', 'required' => false],
                ],
            ],
            // EEP vacancy
            [
                'institution' => 'EEP',
                'code'        => 'EEP-2026-001',
                'title'       => ['en' => 'Electrical Engineer', 'am' => 'የኤሌክትሪካል መሐንዲስ'],
                'department'  => 'Operations & Maintenance',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Various Regions', 'am' => 'የተለያዩ ክልሎች'],
                'positions'   => 10,
                'salary'      => 'Grade 7',
                'field'       => 'Electrical Engineering',
                'experience'  => 2,
                'description' => ['en' => 'Ethiopian Electric Power is hiring Electrical Engineers to support national grid expansion and maintenance operations across regional power stations.', 'am' => 'የኢትዮጵያ ኤሌክትሪክ ኃይል ብሔራዊ ኤሌክትሪክ ኔትወርክ ማስፋፊያ ስራዎችን ለመደገፍ ኤሌክትሪካል መሐንዲሶችን እየቀጠረ ነው።'],
                'requirements' => ['en' => "• BSc in Electrical Engineering\n• 2+ years experience in power systems\n• Willingness to work in regional areas\n• Valid driving license", 'am' => "• በኤሌክትሪካል ምህንድስና ቢ.ኤስ.ሲ ዲግሪ\n• 2+ ዓመት በኃይል ስርዓቶች ልምድ"],
                'opening'     => now()->subDays(1)->toDateString(),
                'closing'     => now()->addDays(30)->toDateString(),
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'Educational Certificate', 'required' => true],
                    ['name' => 'Work Experience Letter', 'required' => true],
                    ['name' => 'Driving License', 'required' => false],
                ],
            ],
            // AAU vacancy
            [
                'institution' => 'AAU',
                'code'        => 'AAU-2026-001',
                'title'       => ['en' => 'Assistant Professor — Computer Science', 'am' => 'ረዳት ፕሮፌሰር — ኮምፒዩተር ሳይንስ'],
                'department'  => 'School of Information Technology',
                'employment_type' => EmploymentType::Permanent,
                'location'    => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
                'positions'   => 2,
                'salary'      => 'Academic Grade A',
                'field'       => 'Computer Science / Information Technology',
                'experience'  => 3,
                'description' => ['en' => 'Addis Ababa University invites applications for the position of Assistant Professor in the School of Information Technology. The successful candidate will teach undergraduate and postgraduate courses.', 'am' => 'አዲስ አበባ ዩኒቨርሲቲ በኢንፎርሜሽን ቴክኖሎጂ ትምህርት ቤት ውስጥ ለረዳት ፕሮፌሰር ቦታ ዕጩዎችን ይጋብዛል።'],
                'requirements' => ['en' => "• PhD in Computer Science or closely related field\n• 3+ years teaching experience at university level\n• Active research publications\n• Experience with curriculum development", 'am' => "• በኮምፒዩተር ሳይንስ PhD ዲግሪ\n• 3+ ዓመት በዩኒቨርሲቲ ደረጃ ማስተማር ልምድ"],
                'opening'     => now()->subDays(10)->toDateString(),
                'closing'     => now()->addDays(45)->toDateString(),
                'status'      => VacancyStatus::Open,
                'documents'   => [
                    ['name' => 'CV / Resume', 'required' => true],
                    ['name' => 'PhD Certificate', 'required' => true],
                    ['name' => 'Research Publications List', 'required' => true],
                    ['name' => 'Reference Letters (2)', 'required' => false],
                ],
            ],
        ];

        $institutionMap = Institution::pluck('id', 'code')->all();
        $createdVacancies = [];

        foreach ($vacancyData as $v) {
            if (Vacancy::where('code', $v['code'])->exists()) {
                $createdVacancies[$v['code']] = Vacancy::where('code', $v['code'])->first();
                continue;
            }

            $vacancy = Vacancy::create([
                'institution_id'            => $institutionMap[$v['institution']] ?? null,
                'code'                      => $v['code'],
                'title'                     => $v['title'],
                'department'                => $v['department'],
                'employment_type'           => $v['employment_type'],
                'location'                  => $v['location'],
                'number_of_positions'       => $v['positions'],
                'salary_grade'              => $v['salary'],
                'field_of_study'            => $v['field'],
                'minimum_experience'        => $v['experience'],
                'description'               => $v['description'],
                'qualification_requirements' => $v['requirements'],
                'opening_date'              => $v['opening'],
                'closing_date'              => $v['closing'],
                'status'                    => $v['status'],
                'published_at'              => now()->subDays(rand(1, 7)),
                'created_by'                => $admin->id,
            ]);

            foreach ($v['documents'] as $doc) {
                VacancyDocument::create([
                    'vacancy_id'    => $vacancy->id,
                    'document_name' => $doc['name'],
                    'is_required'   => $doc['required'],
                    'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
                    'max_size_mb'   => 2,
                ]);
            }

            $createdVacancies[$v['code']] = $vacancy;
        }

        // ── 4. Applicants ────────────────────────────────────────────────────
        $applicantsData = [
            ['first' => 'Abebe',     'last' => 'Kebede',   'email' => 'abebe.kebede@gmail.com',   'phone' => '+251911234561', 'national_id' => 'ETH00123456001', 'gender' => 'male',   'uni' => 'Addis Ababa University',     'field' => 'Computer Science',     'edu' => 'degree',  'gpa' => 3.75, 'exp_y' => 3, 'employer' => 'Ethio Telecom',  'locale' => 'am'],
            ['first' => 'Tigist',    'last' => 'Haile',    'email' => 'tigist.haile@yahoo.com',    'phone' => '+251922345672', 'national_id' => 'ETH00123456002', 'gender' => 'female', 'uni' => 'Jimma University',           'field' => 'Public Health',        'edu' => 'masters', 'gpa' => 3.90, 'exp_y' => 5, 'employer' => 'WHO Ethiopia',   'locale' => 'en'],
            ['first' => 'Dawit',     'last' => 'Tadesse',  'email' => 'dawit.tadesse@hotmail.com', 'phone' => '+251933456783', 'national_id' => 'ETH00123456003', 'gender' => 'male',   'uni' => 'Bahir Dar University',       'field' => 'Civil Engineering',    'edu' => 'degree',  'gpa' => 3.55, 'exp_y' => 6, 'employer' => 'Constr. Co.',    'locale' => 'am'],
            ['first' => 'Hana',      'last' => 'Girma',    'email' => 'hana.girma@gmail.com',      'phone' => '+251944567894', 'national_id' => 'ETH00123456004', 'gender' => 'female', 'uni' => 'Hawassa University',         'field' => 'Education',            'edu' => 'masters', 'gpa' => 3.80, 'exp_y' => 4, 'employer' => 'School Mgmt',    'locale' => 'en'],
            ['first' => 'Yonas',     'last' => 'Bekele',   'email' => 'yonas.bekele@gmail.com',    'phone' => '+251955678905', 'national_id' => 'ETH00123456005', 'gender' => 'male',   'uni' => 'Mekelle University',         'field' => 'Electrical Engineering','edu' => 'degree',  'gpa' => 3.60, 'exp_y' => 2, 'employer' => 'EEPCo',          'locale' => 'am'],
            ['first' => 'Meron',     'last' => 'Assefa',   'email' => 'meron.assefa@gmail.com',    'phone' => '+251966789016', 'national_id' => 'ETH00123456006', 'gender' => 'female', 'uni' => 'Addis Ababa University',     'field' => 'Computer Science',     'edu' => 'masters', 'gpa' => 3.95, 'exp_y' => 4, 'employer' => 'Safaricom Ethiopia','locale' => 'en'],
            ['first' => 'Solomon',   'last' => 'Wolde',    'email' => 'solomon.wolde@gmail.com',   'phone' => '+251977890127', 'national_id' => 'ETH00123456007', 'gender' => 'male',   'uni' => 'Arba Minch University',      'field' => 'Urban Planning',       'edu' => 'degree',  'gpa' => 3.40, 'exp_y' => 5, 'employer' => 'City Admin',     'locale' => 'am'],
            ['first' => 'Selam',     'last' => 'Tesfaye',  'email' => 'selam.tesfaye@gmail.com',   'phone' => '+251988901238', 'national_id' => 'ETH00123456008', 'gender' => 'female', 'uni' => 'Gondar University',          'field' => 'Public Health',        'edu' => 'degree',  'gpa' => 3.65, 'exp_y' => 3, 'employer' => 'Regional Health', 'locale' => 'en'],
            ['first' => 'Biruk',     'last' => 'Alemu',    'email' => 'biruk.alemu@gmail.com',     'phone' => '+251999012349', 'national_id' => 'ETH00123456009', 'gender' => 'male',   'uni' => 'Dilla University',           'field' => 'Education',            'edu' => 'degree',  'gpa' => 3.50, 'exp_y' => 2, 'employer' => null,              'locale' => 'am'],
            ['first' => 'Fikirte',   'last' => 'Negash',   'email' => 'fikirte.negash@gmail.com',  'phone' => '+251900123450', 'national_id' => 'ETH00123456010', 'gender' => 'female', 'uni' => 'St. Mary University',        'field' => 'Information Technology','edu' => 'degree',  'gpa' => 3.70, 'exp_y' => 1, 'employer' => 'Freelance',      'locale' => 'en'],
        ];

        $createdApplicants = [];
        foreach ($applicantsData as $a) {
            if (User::where('email', $a['email'])->exists()) {
                $user = User::where('email', $a['email'])->first();
                $createdApplicants[] = $user->applicant;
                continue;
            }

            $user = User::create([
                'name'               => $a['first'] . ' ' . $a['last'],
                'email'              => $a['email'],
                'phone'              => $a['phone'],
                'password'           => Hash::make('password'),
                'status'             => 'active',
                'preferred_locale'   => $a['locale'],
                'email_verified_at'  => now(),
            ]);
            $user->assignRole('applicant');

            $applicant = Applicant::create([
                'user_id'               => $user->id,
                'first_name'            => $a['first'],
                'last_name'             => $a['last'],
                'full_name'             => $a['first'] . ' ' . $a['last'],
                'email'                 => $a['email'],
                'phone'                 => $a['phone'],
                'national_id'           => $a['national_id'],
                'gender'                => $a['gender'],
                'date_of_birth'         => now()->subYears(rand(24, 38))->subDays(rand(0, 365)),
                'nationality'           => 'Ethiopian',
                'university_name'       => $a['uni'],
                'field_of_study'        => $a['field'],
                'graduation_year'       => now()->subYears($a['exp_y'] + 1)->year,
                'graduation_date'       => now()->subYears($a['exp_y'] + 1)->subMonths(6),
                'gpa'                   => $a['gpa'],
                'education_level'       => $a['edu'],
                'work_experience_years' => $a['exp_y'],
                'work_experience_months'=> 0,
                'current_employer'      => $a['employer'],
                'current_position'      => $a['employer'] ? 'Officer' : null,
                'region'                => 'Addis Ababa',
                'city'                  => 'Addis Ababa',
                'address'               => 'Addis Ababa, Ethiopia',
                'preferred_locale'      => $a['locale'],
                'disability_status'     => false,
            ]);

            $createdApplicants[] = $applicant;
        }

        // ── 5. Applications ──────────────────────────────────────────────────
        // Map field → best matching vacancy codes
        $applicationMatrix = [
            // applicant index => [vacancy codes to apply to]
            0 => ['MOE-2026-002', 'AAU-2026-001'],          // CS background
            1 => ['MOH-2026-001'],                           // Public health
            2 => ['AACA-2026-001', 'EEP-2026-001'],          // Civil/Electrical
            3 => ['MOE-2026-001'],                           // Education
            4 => ['EEP-2026-001'],                           // Electrical
            5 => ['MOE-2026-002', 'AAU-2026-001'],           // CS/IT
            6 => ['AACA-2026-001'],                          // Urban planning
            7 => ['MOH-2026-001'],                           // Public health
            8 => ['MOE-2026-001'],                           // Education
            9 => ['MOE-2026-002'],                           // IT
        ];

        $statuses = [
            ApplicationStatus::Submitted,
            ApplicationStatus::Submitted,
            ApplicationStatus::UnderReview,
            ApplicationStatus::UnderReview,
            ApplicationStatus::PassedScreening,
            ApplicationStatus::FailedScreening,
            ApplicationStatus::CorrectionRequired,
        ];

        foreach ($applicationMatrix as $appIdx => $vacancyCodes) {
            if (! isset($createdApplicants[$appIdx])) {
                continue;
            }
            $applicant = $createdApplicants[$appIdx];

            foreach ($vacancyCodes as $code) {
                if (! isset($createdVacancies[$code])) {
                    continue;
                }
                $vacancy = $createdVacancies[$code];

                // Skip if already applied
                if ($applicant->hasAppliedTo($vacancy)) {
                    continue;
                }

                $status = $statuses[array_rand($statuses)];

                Application::create([
                    'applicant_id'    => $applicant->id,
                    'vacancy_id'      => $vacancy->id,
                    'field_of_study'  => $applicant->field_of_study,
                    'graduation_date' => $applicant->graduation_date,
                    'cgpa'            => $applicant->gpa,
                    'status'          => $status,
                    'submitted_at'    => now()->subDays(rand(1, 14)),
                    'screening_status' => null,
                ]);
            }
        }

        // ── 6. Announcements ─────────────────────────────────────────────────
        $announcements = [
            [
                'subject' => 'New Recruitment Cycle — June 2026',
                'content' => '<p>We are pleased to announce the launch of our <strong>June 2026 recruitment cycle</strong>. Multiple institutions are posting vacancies across various sectors including Education, Health, Engineering, and ICT.</p><p>Applicants are encouraged to review the eligibility requirements carefully before submitting their applications. All applications must be submitted through this portal.</p><p>The deadline for most positions closes by end of June 2026. Apply early to avoid last-minute technical issues.</p>',
                'published_at' => now()->subDays(3),
            ],
            [
                'subject' => 'Important: Application Submission Guidelines',
                'content' => '<p>Please read the following guidelines before submitting your application:</p><ul><li><strong>All documents</strong> must be uploaded in PDF, JPG, or PNG format (max 2 MB each).</li><li><strong>Educational certificates</strong> must be attested/authenticated.</li><li><strong>Work experience letters</strong> must be on official letterhead and signed.</li><li>Incomplete applications will not be considered for further review.</li></ul><p>For technical support, contact our helpdesk at <strong>support@recruitment.gov.et</strong>.</p>',
                'published_at' => now()->subDays(7),
            ],
            [
                'subject' => 'ማስታወቂያ — አዲስ የምልመላ ዑደት',
                'content' => '<p>የ<strong>ሰኔ 2026 ምልመላ ዑደት</strong> መጀመሩን በደስታ እናሳውቃለን። በትምህርት፣ ጤና፣ ምህንድስና እና አይቲ ዘርፎች ብዙ ተቋማት የስራ ቦታዎችን እየለቀቁ ነው።</p><p>ዕጩዎች ማመልከቻ ከማስገባታቸው በፊት የብቃት መስፈርቶቹን በጥንቃቄ እንዲያጠኑ ይበረታታሉ።</p>',
                'published_at' => now()->subDays(2),
            ],
            [
                'subject' => 'Result Announcement — March 2026 Recruitment',
                'content' => '<p>The results for the <strong>March 2026 Recruitment</strong> have been finalized and communicated to successful candidates via email and SMS.</p><p>Applicants who were not selected may re-apply for future openings. We appreciate the interest of all candidates who participated in this recruitment cycle.</p><p>Offer letters will be sent to selected candidates within the next 5 business days.</p>',
                'published_at' => now()->subDays(14),
            ],
        ];

        foreach ($announcements as $ann) {
            VacancyAnnouncement::firstOrCreate(
                ['subject' => $ann['subject']],
                [
                    'content'      => $ann['content'],
                    'published_at' => $ann['published_at'],
                    'created_by'   => $admin->id,
                ]
            );
        }

        $this->command->info('✓ Sample data seeded:');
        $this->command->info('  • ' . count($createdInstitutions) . ' institutions');
        $this->command->info('  • ' . count($createdVacancies) . ' vacancies');
        $this->command->info('  • ' . count($createdApplicants) . ' applicants (password: password)');
        $this->command->info('  • Applications created across vacancies');
        $this->command->info('  • ' . count($announcements) . ' announcements');
    }
}
