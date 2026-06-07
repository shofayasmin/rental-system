<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use SplFileObject;

class BuildSyntheticImportSql extends Command
{
    protected $signature = 'synthetic:build-sql
        {--mode=replace : Import mode: replace or append}
        {--input=storage/app/synthetic-import/templates : Input directory containing CSV sheets}
        {--output=storage/app/synthetic-import/synthetic_import.sql : Output SQL file path}';

    protected $description = 'Build a single SQL import file from synthetic CSV sheets.';

    public function handle(): int
    {
        $mode = strtolower((string) $this->option('mode'));
        if (!in_array($mode, ['replace', 'append'], true)) {
            $this->error('Invalid mode. Allowed values: replace, append.');

            return self::INVALID;
        }

        $inputDir = $this->resolvePath((string) $this->option('input'));
        $outputFile = $this->resolvePath((string) $this->option('output'));

        if (!is_dir($inputDir)) {
            $this->error("Input directory not found: {$inputDir}");

            return self::FAILURE;
        }

        $this->info("Mode: {$mode}");
        $this->info("Input directory: {$inputDir}");
        $this->info("Output file: {$outputFile}");

        try {
            $data = $this->loadCsvSheets($inputDir);
            $context = $this->buildContext($data, $mode);
            $sql = $this->buildSql($context, $mode);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            $this->error("Failed to create output directory: {$outputDir}");

            return self::FAILURE;
        }

        file_put_contents($outputFile, $sql);

        $this->info('SQL file generated successfully.');
        $this->line("Rows summary:");
        foreach ($context['summary'] as $label => $count) {
            $this->line("- {$label}: {$count}");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function loadCsvSheets(string $inputDir): array
    {
        $expected = [
            '00_config',
            '01_users',
            '02_properties',
            '03_property_facility',
            '04_availability_cycles',
            '05_rental_requests',
            '06_transactions',
            '07_contracts',
            '08_contract_extensions',
            '09_conversations',
            '10_conversation_messages',
        ];

        $sheets = [];
        foreach ($expected as $name) {
            $path = rtrim($inputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "{$name}.csv";
            if (!is_file($path)) {
                throw new RuntimeException("Missing required CSV file: {$path}");
            }

            $sheets[$name] = $this->readCsvAssoc($path);
        }

        return $sheets;
    }

    /**
     * @param  array<string, array<int, array<string, string>>>  $data
     * @return array<string, mixed>
     */
    private function buildContext(array $data, string $mode): array
    {
        $errors = [];
        $summary = [];
        $now = now()->format('Y-m-d H:i:s');

        $provinceRows = DB::table('provinces')->select('id', 'code')->get();
        $regencyRows = DB::table('regencies')->select('id', 'code', 'province_id')->get();
        $districtRows = DB::table('districts')->select('id', 'code', 'regency_id')->get();
        $villageRows = DB::table('villages')->select('id', 'code', 'district_id')->get();
        $facilityRows = DB::table('facilities')->select('id', 'slug')->get();

        if ($provinceRows->isEmpty() || $regencyRows->isEmpty() || $districtRows->isEmpty() || $villageRows->isEmpty()) {
            throw new RuntimeException('Location master tables are empty. Seed provinces/regencies/districts/villages first.');
        }

        if ($facilityRows->isEmpty()) {
            throw new RuntimeException('Facilities master table is empty. Seed facilities first.');
        }

        $provinceMap = [];
        foreach ($provinceRows as $row) {
            $provinceMap[(string) $row->code] = (int) $row->id;
        }

        $regencyMap = [];
        foreach ($regencyRows as $row) {
            $regencyMap[(string) $row->code] = ['id' => (int) $row->id, 'province_id' => (int) $row->province_id];
        }

        $districtMap = [];
        foreach ($districtRows as $row) {
            $districtMap[(string) $row->code] = ['id' => (int) $row->id, 'regency_id' => (int) $row->regency_id];
        }

        $villageMap = [];
        foreach ($villageRows as $row) {
            $villageMap[(string) $row->code] = ['id' => (int) $row->id, 'district_id' => (int) $row->district_id];
        }

        $facilityMap = [];
        foreach ($facilityRows as $row) {
            $facilityMap[(string) $row->slug] = (int) $row->id;
        }

        $idSeed = [
            'users' => $mode === 'append' ? ((int) DB::table('users')->max('id') + 1) : 1,
            'properties' => $mode === 'append' ? ((int) DB::table('properties')->max('id') + 1) : 1,
            'property_availability_cycles' => $mode === 'append' ? ((int) DB::table('property_availability_cycles')->max('id') + 1) : 1,
            'rental_requests' => $mode === 'append' ? ((int) DB::table('rental_requests')->max('id') + 1) : 1,
            'transactions' => $mode === 'append' ? ((int) DB::table('transactions')->max('id') + 1) : 1,
            'contracts' => $mode === 'append' ? ((int) DB::table('contracts')->max('id') + 1) : 1,
            'contract_extensions' => $mode === 'append' ? ((int) DB::table('contract_extensions')->max('id') + 1) : 1,
            'conversations' => $mode === 'append' ? ((int) DB::table('conversations')->max('id') + 1) : 1,
            'conversation_messages' => $mode === 'append' ? ((int) DB::table('conversation_messages')->max('id') + 1) : 1,
            'property_facility' => $mode === 'append' ? ((int) DB::table('property_facility')->max('id') + 1) : 1,
        ];

        $users = [];
        $userCodeToId = [];
        $userCodeToRole = [];
        $userCodesSeen = [];
        $emailsSeen = [];
        $hasDefaultAdmin = false;

        foreach ($data['01_users'] as $index => $row) {
            $line = $index + 2;
            $userCode = $this->requiredString($row, 'user_code', "01_users line {$line}", $errors);
            $name = $this->requiredString($row, 'name', "01_users line {$line}", $errors);
            $email = strtolower($this->requiredString($row, 'email', "01_users line {$line}", $errors));
            $role = strtolower($this->requiredString($row, 'role', "01_users line {$line}", $errors));
            $enabled = $this->normalizeBool($row['enabled'] ?? '', "01_users line {$line}", $errors);
            $password = trim((string) ($row['password_plain'] ?? ''));
            $phone = $this->nullableString($row['phone'] ?? null);

            if (!in_array($role, ['admin', 'agent', 'tenant'], true)) {
                $errors[] = "01_users line {$line}: invalid role '{$role}'.";
            }

            if ($userCode !== null) {
                if (isset($userCodesSeen[$userCode])) {
                    $errors[] = "01_users line {$line}: duplicate user_code '{$userCode}'.";
                }
                $userCodesSeen[$userCode] = true;
            }

            if ($email !== '') {
                if (isset($emailsSeen[$email])) {
                    $errors[] = "01_users line {$line}: duplicate email '{$email}' inside CSV.";
                }
                $emailsSeen[$email] = true;
            }

            if ($email === 'admin@test.com' && $role === 'admin') {
                $hasDefaultAdmin = true;
            }

            if ($mode === 'append' && $email !== '' && DB::table('users')->where('email', $email)->exists()) {
                $errors[] = "01_users line {$line}: email '{$email}' already exists in database (append mode).";
            }

            if ($userCode === null || $name === null || $email === '' || $enabled === null || $role === '') {
                continue;
            }

            $assignedId = $idSeed['users']++;
            $userCodeToId[$userCode] = $assignedId;
            $userCodeToRole[$userCode] = $role;

            $users[] = [
                $assignedId,
                $name,
                $email,
                Hash::make($password !== '' ? $password : 'password123'),
                $role,
                $phone,
                $enabled,
                null,
                null,
                $now,
                $now,
            ];
        }

        if (!$hasDefaultAdmin) {
            $errors[] = "01_users: must include an admin row with email 'admin@test.com'.";
        }

        $properties = [];
        $propertyCodeToId = [];
        $propertyCodesSeen = [];
        $allowedPropertyStatus = ['to-let', 'rented', 'maintenance'];

        foreach ($data['02_properties'] as $index => $row) {
            $line = $index + 2;
            $propertyCode = $this->requiredString($row, 'property_code', "02_properties line {$line}", $errors);
            $agentCode = $this->requiredString($row, 'agent_code', "02_properties line {$line}", $errors);
            $title = $this->requiredString($row, 'title', "02_properties line {$line}", $errors);
            $status = strtolower($this->requiredString($row, 'status', "02_properties line {$line}", $errors));
            $rentPrice = $this->requiredNumeric($row, 'rent_price', "02_properties line {$line}", $errors);
            $bedrooms = $this->requiredInt($row, 'bedrooms', "02_properties line {$line}", $errors);
            $bathrooms = $this->requiredNumeric($row, 'bathrooms', "02_properties line {$line}", $errors);

            if ($propertyCode !== null && isset($propertyCodesSeen[$propertyCode])) {
                $errors[] = "02_properties line {$line}: duplicate property_code '{$propertyCode}'.";
            }
            if ($propertyCode !== null) {
                $propertyCodesSeen[$propertyCode] = true;
            }

            if (!in_array($status, $allowedPropertyStatus, true)) {
                $errors[] = "02_properties line {$line}: invalid status '{$status}'.";
            }

            if ($agentCode !== null) {
                if (!isset($userCodeToId[$agentCode])) {
                    $errors[] = "02_properties line {$line}: unknown agent_code '{$agentCode}'.";
                } elseif (($userCodeToRole[$agentCode] ?? '') !== 'agent') {
                    $errors[] = "02_properties line {$line}: agent_code '{$agentCode}' is not role=agent.";
                }
            }

            $provinceCode = $this->requiredString($row, 'province_code', "02_properties line {$line}", $errors);
            $regencyCode = $this->requiredString($row, 'regency_code', "02_properties line {$line}", $errors);
            $districtCode = $this->requiredString($row, 'district_code', "02_properties line {$line}", $errors);
            $villageCode = $this->requiredString($row, 'village_code', "02_properties line {$line}", $errors);

            $provinceId = null;
            $regencyId = null;
            $districtId = null;
            $villageId = null;

            if ($provinceCode !== null) {
                $provinceId = $provinceMap[$provinceCode] ?? null;
                if ($provinceId === null) {
                    $errors[] = "02_properties line {$line}: unknown province_code '{$provinceCode}'.";
                }
            }

            if ($regencyCode !== null) {
                $regency = $regencyMap[$regencyCode] ?? null;
                if ($regency === null) {
                    $errors[] = "02_properties line {$line}: unknown regency_code '{$regencyCode}'.";
                } else {
                    $regencyId = $regency['id'];
                    if ($provinceId !== null && $regency['province_id'] !== $provinceId) {
                        $errors[] = "02_properties line {$line}: regency_code '{$regencyCode}' is not under province_code '{$provinceCode}'.";
                    }
                }
            }

            if ($districtCode !== null) {
                $district = $districtMap[$districtCode] ?? null;
                if ($district === null) {
                    $errors[] = "02_properties line {$line}: unknown district_code '{$districtCode}'.";
                } else {
                    $districtId = $district['id'];
                    if ($regencyId !== null && $district['regency_id'] !== $regencyId) {
                        $errors[] = "02_properties line {$line}: district_code '{$districtCode}' is not under regency_code '{$regencyCode}'.";
                    }
                }
            }

            if ($villageCode !== null) {
                $village = $villageMap[$villageCode] ?? null;
                if ($village === null) {
                    $errors[] = "02_properties line {$line}: unknown village_code '{$villageCode}'.";
                } else {
                    $villageId = $village['id'];
                    if ($districtId !== null && $village['district_id'] !== $districtId) {
                        $errors[] = "02_properties line {$line}: village_code '{$villageCode}' is not under district_code '{$districtCode}'.";
                    }
                }
            }

            $latitude = $this->nullableNumeric($row['latitude'] ?? null, "02_properties line {$line}", 'latitude', $errors);
            $longitude = $this->nullableNumeric($row['longitude'] ?? null, "02_properties line {$line}", 'longitude', $errors);
            if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
                $errors[] = "02_properties line {$line}: latitude out of range (-90..90).";
            }
            if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
                $errors[] = "02_properties line {$line}: longitude out of range (-180..180).";
            }

            $floors = $this->nullableInt($row['floors'] ?? null, "02_properties line {$line}", 'floors', $errors);
            $area = $this->nullableNumeric($row['area'] ?? null, "02_properties line {$line}", 'area', $errors);
            $buildingArea = $this->nullableNumeric($row['building_area'] ?? null, "02_properties line {$line}", 'building_area', $errors);
            $address = $this->nullableString($row['address'] ?? null);
            $description = $this->nullableString($row['description'] ?? null);

            if ($propertyCode === null || $agentCode === null || $title === null || $status === '' || $rentPrice === null || $bedrooms === null || $bathrooms === null || $provinceId === null || $regencyId === null || $districtId === null || $villageId === null) {
                continue;
            }

            $assignedId = $idSeed['properties']++;
            $propertyCodeToId[$propertyCode] = $assignedId;

            $properties[] = [
                $assignedId,
                $userCodeToId[$agentCode],
                $title,
                $provinceId,
                $regencyId,
                $districtId,
                $villageId,
                $address,
                $description,
                $latitude,
                $longitude,
                $bedrooms,
                $bathrooms,
                $floors,
                $area,
                $buildingArea,
                null,
                $rentPrice,
                $status,
                $now,
                $now,
            ];
        }

        $propertyAgentMap = [];
        foreach ($properties as $row) {
            $propertyAgentMap[$row[0]] = $row[1];
        }

        $propertyFacilityRows = [];
        $propertyFacilityUnique = [];
        foreach ($data['03_property_facility'] as $index => $row) {
            $line = $index + 2;
            $propertyCode = $this->requiredString($row, 'property_code', "03_property_facility line {$line}", $errors);
            $facilitySlug = $this->requiredString($row, 'facility_slug', "03_property_facility line {$line}", $errors);
            $value = $this->nullableString($row['value'] ?? null);

            if ($propertyCode !== null && !isset($propertyCodeToId[$propertyCode])) {
                $errors[] = "03_property_facility line {$line}: unknown property_code '{$propertyCode}'.";
            }

            if ($facilitySlug !== null && !isset($facilityMap[$facilitySlug])) {
                $errors[] = "03_property_facility line {$line}: unknown facility_slug '{$facilitySlug}'.";
            }

            if ($propertyCode !== null && $facilitySlug !== null) {
                $uniqueKey = "{$propertyCode}|{$facilitySlug}";
                if (isset($propertyFacilityUnique[$uniqueKey])) {
                    $errors[] = "03_property_facility line {$line}: duplicate pair '{$uniqueKey}'.";
                }
                $propertyFacilityUnique[$uniqueKey] = true;
            }

            if ($propertyCode === null || $facilitySlug === null || !isset($propertyCodeToId[$propertyCode]) || !isset($facilityMap[$facilitySlug])) {
                continue;
            }

            $propertyFacilityRows[] = [
                $idSeed['property_facility']++,
                $propertyCodeToId[$propertyCode],
                $facilityMap[$facilitySlug],
                $value,
                $now,
                $now,
            ];
        }

        $cycleRows = [];
        $cycleCodeToId = [];
        $cycleCodeToPropertyCode = [];
        $cycleCodesSeen = [];
        $allowedClosedBy = ['rented', 'maintenance', 'manual'];
        foreach ($data['04_availability_cycles'] as $index => $row) {
            $line = $index + 2;
            $cycleCode = $this->requiredString($row, 'cycle_code', "04_availability_cycles line {$line}", $errors);
            $propertyCode = $this->requiredString($row, 'property_code', "04_availability_cycles line {$line}", $errors);
            $availableFrom = $this->requiredDateTime($row, 'available_from_at', "04_availability_cycles line {$line}", $errors);
            $unavailableAt = $this->nullableDateTime($row['unavailable_at'] ?? null, "04_availability_cycles line {$line}", 'unavailable_at', $errors);
            $closedBy = $this->nullableString($row['closed_by'] ?? null);

            if ($cycleCode !== null && isset($cycleCodesSeen[$cycleCode])) {
                $errors[] = "04_availability_cycles line {$line}: duplicate cycle_code '{$cycleCode}'.";
            }
            if ($cycleCode !== null) {
                $cycleCodesSeen[$cycleCode] = true;
            }

            if ($propertyCode !== null && !isset($propertyCodeToId[$propertyCode])) {
                $errors[] = "04_availability_cycles line {$line}: unknown property_code '{$propertyCode}'.";
            }

            if ($closedBy !== null && !in_array($closedBy, $allowedClosedBy, true)) {
                $errors[] = "04_availability_cycles line {$line}: invalid closed_by '{$closedBy}'.";
            }

            if ($availableFrom !== null && $unavailableAt !== null && strtotime($unavailableAt) < strtotime($availableFrom)) {
                $errors[] = "04_availability_cycles line {$line}: unavailable_at cannot be earlier than available_from_at.";
            }

            if ($cycleCode === null || $propertyCode === null || $availableFrom === null || !isset($propertyCodeToId[$propertyCode])) {
                continue;
            }

            $assignedId = $idSeed['property_availability_cycles']++;
            $cycleCodeToId[$cycleCode] = $assignedId;
            $cycleCodeToPropertyCode[$cycleCode] = $propertyCode;

            $cycleRows[] = [
                $assignedId,
                $propertyCodeToId[$propertyCode],
                $availableFrom,
                $unavailableAt,
                $closedBy,
                $now,
                $now,
            ];
        }

        $requestRows = [];
        $requestCodeToId = [];
        $requestCodeToStatus = [];
        $requestCodeToRefs = [];
        $requestCodesSeen = [];
        $allowedRequestStatus = [
            'pending_review',
            'awaiting_payment',
            'paid',
            'rejected',
            'cancelled_by_tenant',
            'cancelled_by_agent',
            'cancelled_lost',
        ];

        foreach ($data['05_rental_requests'] as $index => $row) {
            $line = $index + 2;
            $requestCode = $this->requiredString($row, 'request_code', "05_rental_requests line {$line}", $errors);
            $propertyCode = $this->requiredString($row, 'property_code', "05_rental_requests line {$line}", $errors);
            $tenantCode = $this->requiredString($row, 'tenant_code', "05_rental_requests line {$line}", $errors);
            $cycleCode = $this->requiredString($row, 'cycle_code', "05_rental_requests line {$line}", $errors);
            $status = strtolower($this->requiredString($row, 'status', "05_rental_requests line {$line}", $errors));
            $createdAt = $this->requiredDateTime($row, 'created_at', "05_rental_requests line {$line}", $errors);
            $awaitingPaymentAt = $this->nullableDateTime($row['awaiting_payment_at'] ?? null, "05_rental_requests line {$line}", 'awaiting_payment_at', $errors);
            $paymentDueAt = $this->nullableDateTime($row['payment_due_at'] ?? null, "05_rental_requests line {$line}", 'payment_due_at', $errors);
            $paidAt = $this->nullableDateTime($row['paid_at'] ?? null, "05_rental_requests line {$line}", 'paid_at', $errors);
            $rejectedAt = $this->nullableDateTime($row['rejected_at'] ?? null, "05_rental_requests line {$line}", 'rejected_at', $errors);
            $cancelledAt = $this->nullableDateTime($row['cancelled_at'] ?? null, "05_rental_requests line {$line}", 'cancelled_at', $errors);

            if ($requestCode !== null && isset($requestCodesSeen[$requestCode])) {
                $errors[] = "05_rental_requests line {$line}: duplicate request_code '{$requestCode}'.";
            }
            if ($requestCode !== null) {
                $requestCodesSeen[$requestCode] = true;
            }

            if (!in_array($status, $allowedRequestStatus, true)) {
                $errors[] = "05_rental_requests line {$line}: invalid status '{$status}'.";
            }

            if ($tenantCode !== null) {
                if (!isset($userCodeToId[$tenantCode])) {
                    $errors[] = "05_rental_requests line {$line}: unknown tenant_code '{$tenantCode}'.";
                } elseif (($userCodeToRole[$tenantCode] ?? '') !== 'tenant') {
                    $errors[] = "05_rental_requests line {$line}: tenant_code '{$tenantCode}' is not role=tenant.";
                }
            }

            if ($propertyCode !== null && !isset($propertyCodeToId[$propertyCode])) {
                $errors[] = "05_rental_requests line {$line}: unknown property_code '{$propertyCode}'.";
            }

            if ($cycleCode !== null) {
                if (!isset($cycleCodeToId[$cycleCode])) {
                    $errors[] = "05_rental_requests line {$line}: unknown cycle_code '{$cycleCode}'.";
                } elseif (($cycleCodeToPropertyCode[$cycleCode] ?? null) !== $propertyCode) {
                    $errors[] = "05_rental_requests line {$line}: cycle_code '{$cycleCode}' does not belong to property_code '{$propertyCode}'.";
                }
            }

            if ($status === 'paid' && $paidAt === null) {
                $errors[] = "05_rental_requests line {$line}: paid status requires paid_at.";
            }
            if ($status === 'awaiting_payment' && ($awaitingPaymentAt === null || $paymentDueAt === null)) {
                $errors[] = "05_rental_requests line {$line}: awaiting_payment status requires awaiting_payment_at and payment_due_at.";
            }
            if ($status === 'rejected' && $rejectedAt === null) {
                $errors[] = "05_rental_requests line {$line}: rejected status requires rejected_at.";
            }
            if (str_starts_with($status, 'cancelled_') && $cancelledAt === null) {
                $errors[] = "05_rental_requests line {$line}: cancelled status requires cancelled_at.";
            }

            if ($requestCode === null || $propertyCode === null || $tenantCode === null || $cycleCode === null || $createdAt === null || !isset($propertyCodeToId[$propertyCode]) || !isset($userCodeToId[$tenantCode]) || !isset($cycleCodeToId[$cycleCode])) {
                continue;
            }

            $assignedId = $idSeed['rental_requests']++;
            $requestCodeToId[$requestCode] = $assignedId;
            $requestCodeToStatus[$requestCode] = $status;
            $requestCodeToRefs[$requestCode] = [
                'property_code' => $propertyCode,
                'tenant_code' => $tenantCode,
            ];

            $requestRows[] = [
                $assignedId,
                $propertyCodeToId[$propertyCode],
                $cycleCodeToId[$cycleCode],
                $userCodeToId[$tenantCode],
                $status,
                $awaitingPaymentAt,
                $paymentDueAt,
                $paidAt,
                $rejectedAt,
                $cancelledAt,
                $createdAt,
                $createdAt,
            ];
        }

        $contractRows = [];
        $contractCodeToId = [];
        $contractCodesSeen = [];
        $requestWithContract = [];
        $allowedContractStatus = ['active', 'ended'];

        foreach ($data['07_contracts'] as $index => $row) {
            $line = $index + 2;
            $contractCode = $this->requiredString($row, 'contract_code', "07_contracts line {$line}", $errors);
            $requestCode = $this->requiredString($row, 'request_code', "07_contracts line {$line}", $errors);
            $startDate = $this->requiredDate($row, 'start_date', "07_contracts line {$line}", $errors);
            $endDate = $this->requiredDate($row, 'end_date', "07_contracts line {$line}", $errors);
            $monthlyRent = $this->requiredNumeric($row, 'monthly_rent', "07_contracts line {$line}", $errors);
            $totalPrice = $this->requiredNumeric($row, 'total_price', "07_contracts line {$line}", $errors);
            $status = strtolower($this->requiredString($row, 'status', "07_contracts line {$line}", $errors));
            $endedReason = $this->nullableString($row['ended_reason'] ?? null);
            $endedByCode = $this->nullableString($row['ended_by_user_code'] ?? null);
            $endedAt = $this->nullableDateTime($row['ended_at'] ?? null, "07_contracts line {$line}", 'ended_at', $errors);

            if ($contractCode !== null && isset($contractCodesSeen[$contractCode])) {
                $errors[] = "07_contracts line {$line}: duplicate contract_code '{$contractCode}'.";
            }
            if ($contractCode !== null) {
                $contractCodesSeen[$contractCode] = true;
            }

            if (!in_array($status, $allowedContractStatus, true)) {
                $errors[] = "07_contracts line {$line}: invalid status '{$status}'.";
            }

            if ($requestCode !== null) {
                if (!isset($requestCodeToId[$requestCode])) {
                    $errors[] = "07_contracts line {$line}: unknown request_code '{$requestCode}'.";
                } elseif (($requestCodeToStatus[$requestCode] ?? '') !== 'paid') {
                    $errors[] = "07_contracts line {$line}: contracts are allowed only for paid requests (request_code '{$requestCode}').";
                } elseif (isset($requestWithContract[$requestCode])) {
                    $errors[] = "07_contracts line {$line}: duplicate contract for request_code '{$requestCode}'.";
                }
            }

            $endedBy = null;
            if ($endedByCode !== null) {
                if (!isset($userCodeToId[$endedByCode])) {
                    $errors[] = "07_contracts line {$line}: unknown ended_by_user_code '{$endedByCode}'.";
                } else {
                    $endedBy = $userCodeToId[$endedByCode];
                }
            }

            if ($contractCode === null || $requestCode === null || $startDate === null || $endDate === null || $monthlyRent === null || $totalPrice === null || !isset($requestCodeToId[$requestCode])) {
                continue;
            }

            $assignedId = $idSeed['contracts']++;
            $contractCodeToId[$contractCode] = $assignedId;
            $requestWithContract[$requestCode] = true;

            $contractRows[] = [
                $assignedId,
                $requestCodeToId[$requestCode],
                $startDate,
                $endDate,
                $totalPrice,
                $monthlyRent,
                $status,
                $endedReason,
                $endedBy,
                $endedAt,
                $now,
                $now,
            ];
        }

        $extensionRows = [];
        $extensionCodeToId = [];
        $extensionCodesSeen = [];
        $allowedExtensionStatus = ['pending', 'awaiting_payment', 'paid', 'rejected', 'cancelled_by_tenant', 'expired'];

        foreach ($data['08_contract_extensions'] as $index => $row) {
            $line = $index + 2;
            $extensionCode = $this->requiredString($row, 'extension_code', "08_contract_extensions line {$line}", $errors);
            $contractCode = $this->requiredString($row, 'contract_code', "08_contract_extensions line {$line}", $errors);
            $oldEndDate = $this->requiredDate($row, 'old_end_date', "08_contract_extensions line {$line}", $errors);
            $newEndDate = $this->requiredDate($row, 'new_end_date', "08_contract_extensions line {$line}", $errors);
            $extendedAt = $this->requiredDateTime($row, 'extended_at', "08_contract_extensions line {$line}", $errors);
            $monthsRequested = $this->requiredInt($row, 'months_requested', "08_contract_extensions line {$line}", $errors);
            $monthlyRentSnapshot = $this->requiredNumeric($row, 'monthly_rent_snapshot', "08_contract_extensions line {$line}", $errors);
            $amount = $this->requiredNumeric($row, 'amount', "08_contract_extensions line {$line}", $errors);
            $status = strtolower($this->requiredString($row, 'status', "08_contract_extensions line {$line}", $errors));
            $approvedByCode = $this->nullableString($row['approved_by_user_code'] ?? null);
            $approvedAt = $this->nullableDateTime($row['approved_at'] ?? null, "08_contract_extensions line {$line}", 'approved_at', $errors);
            $paymentDueAt = $this->nullableDateTime($row['payment_due_at'] ?? null, "08_contract_extensions line {$line}", 'payment_due_at', $errors);
            $paidAt = $this->nullableDateTime($row['paid_at'] ?? null, "08_contract_extensions line {$line}", 'paid_at', $errors);
            $rejectedAt = $this->nullableDateTime($row['rejected_at'] ?? null, "08_contract_extensions line {$line}", 'rejected_at', $errors);
            $cancelledAt = $this->nullableDateTime($row['cancelled_at'] ?? null, "08_contract_extensions line {$line}", 'cancelled_at', $errors);

            if ($extensionCode !== null && isset($extensionCodesSeen[$extensionCode])) {
                $errors[] = "08_contract_extensions line {$line}: duplicate extension_code '{$extensionCode}'.";
            }
            if ($extensionCode !== null) {
                $extensionCodesSeen[$extensionCode] = true;
            }

            if ($contractCode !== null && !isset($contractCodeToId[$contractCode])) {
                $errors[] = "08_contract_extensions line {$line}: unknown contract_code '{$contractCode}'.";
            }

            if (!in_array($status, $allowedExtensionStatus, true)) {
                $errors[] = "08_contract_extensions line {$line}: invalid status '{$status}'.";
            }

            if ($status === 'awaiting_payment' && ($approvedAt === null || $paymentDueAt === null)) {
                $errors[] = "08_contract_extensions line {$line}: awaiting_payment status requires approved_at and payment_due_at.";
            }
            if ($status === 'paid' && $paidAt === null) {
                $errors[] = "08_contract_extensions line {$line}: paid status requires paid_at.";
            }
            if ($status === 'rejected' && $rejectedAt === null) {
                $errors[] = "08_contract_extensions line {$line}: rejected status requires rejected_at.";
            }
            if ($status === 'cancelled_by_tenant' && $cancelledAt === null) {
                $errors[] = "08_contract_extensions line {$line}: cancelled_by_tenant status requires cancelled_at.";
            }

            $approvedBy = null;
            if ($approvedByCode !== null) {
                if (!isset($userCodeToId[$approvedByCode])) {
                    $errors[] = "08_contract_extensions line {$line}: unknown approved_by_user_code '{$approvedByCode}'.";
                } else {
                    $approvedBy = $userCodeToId[$approvedByCode];
                }
            }

            if ($extensionCode === null || $contractCode === null || $oldEndDate === null || $newEndDate === null || $extendedAt === null || $monthsRequested === null || $monthlyRentSnapshot === null || $amount === null || !isset($contractCodeToId[$contractCode])) {
                continue;
            }

            $assignedId = $idSeed['contract_extensions']++;
            $extensionCodeToId[$extensionCode] = $assignedId;

            $extensionRows[] = [
                $assignedId,
                $contractCodeToId[$contractCode],
                $oldEndDate,
                $newEndDate,
                $extendedAt,
                $monthsRequested,
                $monthlyRentSnapshot,
                $amount,
                $status,
                $approvedBy,
                $approvedAt,
                $paymentDueAt,
                $rejectedAt,
                $cancelledAt,
                $paidAt,
                $now,
                $now,
            ];
        }

        $transactionRows = [];
        $txCodesSeen = [];
        $initialRequestPairs = [];
        $allowedTxType = ['initial_rent', 'extension_rent'];
        $allowedTxStatus = ['unpaid', 'paid', 'failed'];

        foreach ($data['06_transactions'] as $index => $row) {
            $line = $index + 2;
            $txCode = $this->requiredString($row, 'tx_code', "06_transactions line {$line}", $errors);
            $requestCode = $this->requiredString($row, 'request_code', "06_transactions line {$line}", $errors);
            $type = strtolower($this->requiredString($row, 'type', "06_transactions line {$line}", $errors));
            $status = strtolower($this->requiredString($row, 'status', "06_transactions line {$line}", $errors));
            $amount = $this->requiredNumeric($row, 'amount', "06_transactions line {$line}", $errors);
            $extensionCode = $this->nullableString($row['extension_code'] ?? null);

            if ($txCode !== null && isset($txCodesSeen[$txCode])) {
                $errors[] = "06_transactions line {$line}: duplicate tx_code '{$txCode}'.";
            }
            if ($txCode !== null) {
                $txCodesSeen[$txCode] = true;
            }

            if (!in_array($type, $allowedTxType, true)) {
                $errors[] = "06_transactions line {$line}: invalid type '{$type}'.";
            }
            if (!in_array($status, $allowedTxStatus, true)) {
                $errors[] = "06_transactions line {$line}: invalid status '{$status}'.";
            }

            if ($requestCode !== null && !isset($requestCodeToId[$requestCode])) {
                $errors[] = "06_transactions line {$line}: unknown request_code '{$requestCode}'.";
            }

            if ($requestCode !== null && $type === 'initial_rent') {
                $pairKey = "{$requestCode}|{$type}";
                if (isset($initialRequestPairs[$pairKey])) {
                    $errors[] = "06_transactions line {$line}: duplicate request_code+type '{$pairKey}'.";
                }
                $initialRequestPairs[$pairKey] = true;
            }

            $extensionId = null;
            if ($type === 'extension_rent') {
                if ($extensionCode === null) {
                    $errors[] = "06_transactions line {$line}: extension_rent requires extension_code.";
                } elseif (!isset($extensionCodeToId[$extensionCode])) {
                    $errors[] = "06_transactions line {$line}: unknown extension_code '{$extensionCode}'.";
                } else {
                    $extensionId = $extensionCodeToId[$extensionCode];
                }
            }

            if ($requestCode !== null && isset($requestCodeToStatus[$requestCode]) && $requestCodeToStatus[$requestCode] === 'paid' && $type === 'initial_rent' && $status !== 'paid') {
                $errors[] = "06_transactions line {$line}: paid request '{$requestCode}' must have initial_rent with status paid.";
            }

            if ($txCode === null || $requestCode === null || $type === '' || $status === '' || $amount === null || !isset($requestCodeToId[$requestCode])) {
                continue;
            }

            $ref = $requestCodeToRefs[$requestCode] ?? null;
            if ($ref === null) {
                continue;
            }

            $propertyCode = $ref['property_code'];
            $tenantCode = $ref['tenant_code'];

            $transactionRows[] = [
                $idSeed['transactions']++,
                $requestCodeToId[$requestCode],
                $propertyCodeToId[$propertyCode],
                $userCodeToId[$tenantCode],
                $propertyAgentMap[$propertyCodeToId[$propertyCode]] ?? null,
                $extensionId,
                $amount,
                $type,
                $status,
                $now,
                $now,
            ];
        }

        $conversationRows = [];
        $conversationCodeToId = [];
        $conversationCodesSeen = [];
        $tenantAgentPairs = [];
        $allowedConversationStatus = ['open', 'closed'];

        foreach ($data['09_conversations'] as $index => $row) {
            $line = $index + 2;
            $conversationCode = $this->requiredString($row, 'conversation_code', "09_conversations line {$line}", $errors);
            $tenantCode = $this->requiredString($row, 'tenant_code', "09_conversations line {$line}", $errors);
            $agentCode = $this->requiredString($row, 'agent_code', "09_conversations line {$line}", $errors);
            $propertyCode = $this->nullableString($row['property_code'] ?? null);
            $requestCode = $this->nullableString($row['request_code'] ?? null);
            $status = strtolower($this->requiredString($row, 'status', "09_conversations line {$line}", $errors));
            $lastMessageAt = $this->nullableDateTime($row['last_message_at'] ?? null, "09_conversations line {$line}", 'last_message_at', $errors);

            if ($conversationCode !== null && isset($conversationCodesSeen[$conversationCode])) {
                $errors[] = "09_conversations line {$line}: duplicate conversation_code '{$conversationCode}'.";
            }
            if ($conversationCode !== null) {
                $conversationCodesSeen[$conversationCode] = true;
            }

            if (!in_array($status, $allowedConversationStatus, true)) {
                $errors[] = "09_conversations line {$line}: invalid status '{$status}'.";
            }

            if ($tenantCode !== null) {
                if (!isset($userCodeToId[$tenantCode])) {
                    $errors[] = "09_conversations line {$line}: unknown tenant_code '{$tenantCode}'.";
                } elseif (($userCodeToRole[$tenantCode] ?? '') !== 'tenant') {
                    $errors[] = "09_conversations line {$line}: tenant_code '{$tenantCode}' is not role=tenant.";
                }
            }

            if ($agentCode !== null) {
                if (!isset($userCodeToId[$agentCode])) {
                    $errors[] = "09_conversations line {$line}: unknown agent_code '{$agentCode}'.";
                } elseif (($userCodeToRole[$agentCode] ?? '') !== 'agent') {
                    $errors[] = "09_conversations line {$line}: agent_code '{$agentCode}' is not role=agent.";
                }
            }

            if ($requestCode !== null && !isset($requestCodeToId[$requestCode])) {
                $errors[] = "09_conversations line {$line}: unknown request_code '{$requestCode}'.";
            }

            if ($propertyCode === null && $requestCode !== null && isset($requestCodeToRefs[$requestCode])) {
                $propertyCode = $requestCodeToRefs[$requestCode]['property_code'];
            }

            if ($propertyCode !== null && !isset($propertyCodeToId[$propertyCode])) {
                $errors[] = "09_conversations line {$line}: unknown property_code '{$propertyCode}'.";
            }

            if ($tenantCode !== null && $agentCode !== null) {
                $pair = "{$tenantCode}|{$agentCode}";
                if (isset($tenantAgentPairs[$pair])) {
                    $errors[] = "09_conversations line {$line}: duplicate tenant+agent pair '{$pair}'.";
                }
                $tenantAgentPairs[$pair] = true;
            }

            if ($conversationCode === null || $tenantCode === null || $agentCode === null || $propertyCode === null || !isset($userCodeToId[$tenantCode]) || !isset($userCodeToId[$agentCode]) || !isset($propertyCodeToId[$propertyCode])) {
                continue;
            }

            $assignedId = $idSeed['conversations']++;
            $conversationCodeToId[$conversationCode] = $assignedId;

            $conversationRows[] = [
                $assignedId,
                $propertyCodeToId[$propertyCode],
                $userCodeToId[$tenantCode],
                $userCodeToId[$agentCode],
                $requestCode !== null ? ($requestCodeToId[$requestCode] ?? null) : null,
                $status,
                $lastMessageAt,
                $now,
                $now,
            ];
        }

        $messageRows = [];
        $messageCodesSeen = [];
        $conversationParticipantMap = [];
        $conversationDefaults = [];

        foreach ($conversationRows as $row) {
            $conversationParticipantMap[$row[0]] = [$row[2], $row[3]];
            $conversationDefaults[$row[0]] = ['property_id' => $row[1], 'request_id' => $row[4]];
        }

        foreach ($data['10_conversation_messages'] as $index => $row) {
            $line = $index + 2;
            $messageCode = $this->requiredString($row, 'message_code', "10_conversation_messages line {$line}", $errors);
            $conversationCode = $this->requiredString($row, 'conversation_code', "10_conversation_messages line {$line}", $errors);
            $senderCode = $this->requiredString($row, 'sender_code', "10_conversation_messages line {$line}", $errors);
            $propertyCode = $this->nullableString($row['property_code'] ?? null);
            $requestCode = $this->nullableString($row['request_code'] ?? null);
            $message = $this->requiredString($row, 'message', "10_conversation_messages line {$line}", $errors);
            $createdAt = $this->requiredDateTime($row, 'created_at', "10_conversation_messages line {$line}", $errors);

            if ($messageCode !== null && isset($messageCodesSeen[$messageCode])) {
                $errors[] = "10_conversation_messages line {$line}: duplicate message_code '{$messageCode}'.";
            }
            if ($messageCode !== null) {
                $messageCodesSeen[$messageCode] = true;
            }

            if ($conversationCode !== null && !isset($conversationCodeToId[$conversationCode])) {
                $errors[] = "10_conversation_messages line {$line}: unknown conversation_code '{$conversationCode}'.";
            }

            if ($senderCode !== null && !isset($userCodeToId[$senderCode])) {
                $errors[] = "10_conversation_messages line {$line}: unknown sender_code '{$senderCode}'.";
            }

            if ($propertyCode !== null && !isset($propertyCodeToId[$propertyCode])) {
                $errors[] = "10_conversation_messages line {$line}: unknown property_code '{$propertyCode}'.";
            }

            if ($requestCode !== null && !isset($requestCodeToId[$requestCode])) {
                $errors[] = "10_conversation_messages line {$line}: unknown request_code '{$requestCode}'.";
            }

            if ($messageCode === null || $conversationCode === null || $senderCode === null || $message === null || $createdAt === null || !isset($conversationCodeToId[$conversationCode]) || !isset($userCodeToId[$senderCode])) {
                continue;
            }

            $conversationId = $conversationCodeToId[$conversationCode];
            $senderId = $userCodeToId[$senderCode];
            $participants = $conversationParticipantMap[$conversationId] ?? null;
            if ($participants === null || !in_array($senderId, $participants, true)) {
                $errors[] = "10_conversation_messages line {$line}: sender_code '{$senderCode}' is not part of conversation '{$conversationCode}'.";
                continue;
            }

            $defaultRefs = $conversationDefaults[$conversationId] ?? ['property_id' => null, 'request_id' => null];
            $propertyId = $propertyCode !== null ? ($propertyCodeToId[$propertyCode] ?? null) : $defaultRefs['property_id'];
            $requestId = $requestCode !== null ? ($requestCodeToId[$requestCode] ?? null) : $defaultRefs['request_id'];

            $messageRows[] = [
                $idSeed['conversation_messages']++,
                $conversationId,
                $senderId,
                $propertyId,
                $requestId,
                $message,
                $createdAt,
                $createdAt,
            ];
        }

        if (!empty($errors)) {
            $preview = array_slice($errors, 0, 30);
            $errorMessage = "Validation failed with " . count($errors) . " issue(s):\n- " . implode("\n- ", $preview);
            if (count($errors) > count($preview)) {
                $errorMessage .= "\n- ... and " . (count($errors) - count($preview)) . " more.";
            }

            throw new RuntimeException($errorMessage);
        }

        $summary = [
            'users' => count($users),
            'properties' => count($properties),
            'property_facility' => count($propertyFacilityRows),
            'property_availability_cycles' => count($cycleRows),
            'rental_requests' => count($requestRows),
            'transactions' => count($transactionRows),
            'contracts' => count($contractRows),
            'contract_extensions' => count($extensionRows),
            'conversations' => count($conversationRows),
            'conversation_messages' => count($messageRows),
        ];

        return [
            'summary' => $summary,
            'users' => $users,
            'properties' => $properties,
            'property_facility' => $propertyFacilityRows,
            'property_availability_cycles' => $cycleRows,
            'rental_requests' => $requestRows,
            'transactions' => $transactionRows,
            'contracts' => $contractRows,
            'contract_extensions' => $extensionRows,
            'conversations' => $conversationRows,
            'conversation_messages' => $messageRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildSql(array $context, string $mode): string
    {
        $lines = [];
        $generatedAt = now()->format('Y-m-d H:i:s');

        $lines[] = '-- Synthetic SQL import file';
        $lines[] = "-- Generated at {$generatedAt}";
        $lines[] = "-- Mode: {$mode}";
        $lines[] = '';
        $lines[] = 'START TRANSACTION;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = '';

        if ($mode === 'replace') {
            $truncateTables = [
                'login_audit',
                'conversation_messages',
                'conversations',
                'contract_extensions',
                'contracts',
                'transactions',
                'rental_requests',
                'property_availability_cycles',
                'property_facility',
                'property_photos',
                'properties',
                'users',
            ];

            foreach ($truncateTables as $table) {
                $lines[] = "TRUNCATE TABLE `{$table}`;";
            }
            $lines[] = '';
        }

        $this->appendInsertBlock(
            $lines,
            'users',
            ['id', 'name', 'email', 'password', 'role', 'phone', 'enabled', 'email_verified_at', 'remember_token', 'created_at', 'updated_at'],
            $context['users']
        );

        $this->appendInsertBlock(
            $lines,
            'properties',
            ['id', 'agent_id', 'title', 'province_id', 'regency_id', 'district_id', 'village_id', 'address', 'description', 'latitude', 'longitude', 'bedrooms', 'bathrooms', 'floors', 'area', 'building_area', 'facilities', 'rent_price', 'status', 'created_at', 'updated_at'],
            $context['properties']
        );

        $this->appendInsertBlock(
            $lines,
            'property_facility',
            ['id', 'property_id', 'facility_id', 'value', 'created_at', 'updated_at'],
            $context['property_facility']
        );

        $this->appendInsertBlock(
            $lines,
            'property_availability_cycles',
            ['id', 'property_id', 'available_from_at', 'unavailable_at', 'closed_by', 'created_at', 'updated_at'],
            $context['property_availability_cycles']
        );

        $this->appendInsertBlock(
            $lines,
            'rental_requests',
            ['id', 'property_id', 'availability_cycle_id', 'tenant_id', 'status', 'awaiting_payment_at', 'payment_due_at', 'paid_at', 'rejected_at', 'cancelled_at', 'created_at', 'updated_at'],
            $context['rental_requests']
        );

        $this->appendInsertBlock(
            $lines,
            'contracts',
            ['id', 'rental_request_id', 'start_date', 'end_date', 'total_price', 'monthly_rent', 'status', 'ended_reason', 'ended_by', 'ended_at', 'created_at', 'updated_at'],
            $context['contracts']
        );

        $this->appendInsertBlock(
            $lines,
            'contract_extensions',
            ['id', 'contract_id', 'old_end_date', 'new_end_date', 'extended_at', 'months_requested', 'monthly_rent_snapshot', 'amount', 'status', 'approved_by', 'approved_at', 'payment_due_at', 'rejected_at', 'cancelled_at', 'paid_at', 'created_at', 'updated_at'],
            $context['contract_extensions']
        );

        $this->appendInsertBlock(
            $lines,
            'transactions',
            ['id', 'rental_request_id', 'property_id', 'tenant_id', 'agent_id', 'contract_extension_id', 'amount', 'type', 'status', 'created_at', 'updated_at'],
            $context['transactions']
        );

        $this->appendInsertBlock(
            $lines,
            'conversations',
            ['id', 'property_id', 'tenant_id', 'agent_id', 'rental_request_id', 'status', 'last_message_at', 'created_at', 'updated_at'],
            $context['conversations']
        );

        $this->appendInsertBlock(
            $lines,
            'conversation_messages',
            ['id', 'conversation_id', 'sender_id', 'property_id', 'rental_request_id', 'message', 'created_at', 'updated_at'],
            $context['conversation_messages']
        );

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = 'COMMIT;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $lines
     */
    private function appendInsertBlock(array &$lines, string $table, array $columns, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $lines[] = "-- {$table}";
        $chunkSize = 300;
        $columnSql = '`' . implode('`,`', $columns) . '`';

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $valueSql = [];
            foreach ($chunk as $row) {
                $escaped = array_map(fn ($value) => $this->sqlValue($value), $row);
                $valueSql[] = '(' . implode(', ', $escaped) . ')';
            }

            $lines[] = "INSERT INTO `{$table}` ({$columnSql}) VALUES";
            $lines[] = implode(",\n", $valueSql) . ';';
        }

        $lines[] = '';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsvAssoc(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(',');

        $headers = null;
        $rows = [];

        foreach ($file as $index => $columns) {
            if ($columns === [null] || $columns === false) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    fn ($h) => trim((string) $h),
                    $columns
                );
                continue;
            }

            $isBlank = true;
            foreach ($columns as $value) {
                if (trim((string) $value) !== '') {
                    $isBlank = false;
                    break;
                }
            }
            if ($isBlank) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($columns[$i] ?? ''));
            }

            $rows[] = $row;
        }

        if ($headers === null) {
            throw new RuntimeException("CSV file has no header row: {$path}");
        }

        return $rows;
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $text = str_replace('\\', '\\\\', (string) $value);
        $text = str_replace("'", "''", $text);

        return "'{$text}'";
    }

    private function resolvePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return base_path();
        }

        if (str_starts_with($trimmed, DIRECTORY_SEPARATOR)) {
            return $trimmed;
        }

        return base_path($trimmed);
    }

    private function requiredString(array $row, string $key, string $context, array &$errors): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value === '') {
            $errors[] = "{$context}: {$key} is required.";

            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function requiredNumeric(array $row, string $key, string $context, array &$errors): ?float
    {
        $raw = trim((string) ($row[$key] ?? ''));
        if ($raw === '' || !is_numeric($raw)) {
            $errors[] = "{$context}: {$key} must be numeric.";

            return null;
        }

        return (float) $raw;
    }

    private function nullableNumeric(mixed $raw, string $context, string $key, array &$errors): ?float
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        if (!is_numeric($text)) {
            $errors[] = "{$context}: {$key} must be numeric.";

            return null;
        }

        return (float) $text;
    }

    private function requiredInt(array $row, string $key, string $context, array &$errors): ?int
    {
        $raw = trim((string) ($row[$key] ?? ''));
        if ($raw === '' || !preg_match('/^-?\d+$/', $raw)) {
            $errors[] = "{$context}: {$key} must be an integer.";

            return null;
        }

        return (int) $raw;
    }

    private function nullableInt(mixed $raw, string $context, string $key, array &$errors): ?int
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        if (!preg_match('/^-?\d+$/', $text)) {
            $errors[] = "{$context}: {$key} must be an integer.";

            return null;
        }

        return (int) $text;
    }

    private function requiredDateTime(array $row, string $key, string $context, array &$errors): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value === '') {
            $errors[] = "{$context}: {$key} is required.";

            return null;
        }

        if (strtotime($value) === false) {
            $errors[] = "{$context}: {$key} is not a valid datetime.";

            return null;
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }

    private function nullableDateTime(mixed $raw, string $context, string $key, array &$errors): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (strtotime($value) === false) {
            $errors[] = "{$context}: {$key} is not a valid datetime.";

            return null;
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }

    private function requiredDate(array $row, string $key, string $context, array &$errors): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value === '') {
            $errors[] = "{$context}: {$key} is required.";

            return null;
        }

        if (strtotime($value) === false) {
            $errors[] = "{$context}: {$key} is not a valid date.";

            return null;
        }

        return date('Y-m-d', strtotime($value));
    }

    private function normalizeBool(mixed $raw, string $context, array &$errors): ?int
    {
        $value = strtolower(trim((string) $raw));
        if (in_array($value, ['1', 'true', 'yes'], true)) {
            return 1;
        }
        if (in_array($value, ['0', 'false', 'no'], true)) {
            return 0;
        }

        $errors[] = "{$context}: enabled must be 0/1.";

        return null;
    }
}
