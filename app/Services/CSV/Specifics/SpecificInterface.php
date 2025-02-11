<?php
/*
 * SpecificInterface.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of the Firefly III Data Importer
 * (https://github.com/firefly-iii/data-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace App\Services\CSV\Specifics;

/**
 * Interface SpecificInterface.
 */
interface SpecificInterface
{
    /**
     * Get description.
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Get name.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * If the fix(es) in your file add or remove columns from the CSV content, this must be reflected on the header row
     * as well.
     *
     * @param array $headers
     *
     * @return array
     */
    public function runOnHeaders(array $headers): array;

    /**
     * Run the fix(es) on a single line of CSV file.
     *
     * @param array $row
     *
     * @return array
     */
    public function run(array $row): array;
}
