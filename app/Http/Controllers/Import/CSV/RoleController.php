<?php
/*
 * RoleController.php
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

namespace App\Http\Controllers\Import\CSV;


use App\Http\Controllers\Controller;
use App\Http\Middleware\RoleControllerMiddleware;
use App\Http\Request\RolesPostRequest;
use App\Services\CSV\Configuration\ConfigFileProcessor;
use App\Services\CSV\Configuration\Configuration;
use App\Services\CSV\Roles\RoleService;
use App\Services\Session\Constants;
use App\Services\Storage\StorageService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

/**
 * Class RoleController
 */
class RoleController extends Controller
{
    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Define roles');
        $this->middleware(RoleControllerMiddleware::class);
    }

    /**
     * @return Factory|View
     */
    public function index(Request $request)
    {
        Log::debug('Now in role controller');
        $flow = $request->cookie(Constants::FLOW_COOKIE);
        if ('csv' !== $flow) {
            die('redirect or something');
        }
        $mainTitle = 'Role definition';
        $subTitle  = 'Configure the role of each column in your file';

        // get configuration object.
        // Read configuration from session, will miss some important keys:
        $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));

        // append configuration from original file
        // because the session omits 3 arrays: mapping, do_mapping and roles
        $configFileName = session()->get(Constants::UPLOAD_CONFIG_FILE);
        if (null !== $configFileName) {
            $fileConfiguration = ConfigFileProcessor::convertConfigFile($configFileName);
            $configuration->setDoMapping($fileConfiguration->getDoMapping());
            $configuration->setMapping($fileConfiguration->getMapping());
            $configuration->setRoles($fileConfiguration->getRoles());
        }

        // get columns from file
        $content  = StorageService::getContent(session()->get(Constants::UPLOAD_CSV_FILE), $configuration->isConversion());
        $columns  = RoleService::getColumns($content, $configuration);
        $examples = RoleService::getExampleData($content, $configuration);

        // submit mapping from config.
        $mapping = base64_encode(json_encode($configuration->getMapping(), JSON_THROW_ON_ERROR));

        // roles
        $roles = config('csv.import_roles');
        ksort($roles);

        // configuration (if it is set)
        $configuredRoles     = $configuration->getRoles();
        $configuredDoMapping = $configuration->getDoMapping();

        return view(
            'import.005-roles.index',
            compact('mainTitle', 'configuration', 'subTitle', 'columns', 'examples', 'roles', 'configuredRoles', 'configuredDoMapping', 'mapping')
        );
    }

    /**
     * @param RolesPostRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(RolesPostRequest $request): RedirectResponse
    {
        $data = $request->getAll();

        // get configuration object.
        // Read configuration from session, may miss some important keys:
        $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));

        $needsMapping = $this->needMapping($data['do_mapping']);
        $configuration->setRoles($data['roles']);
        $configuration->setDoMapping($data['do_mapping']);

        session()->put(Constants::CONFIGURATION, $configuration->toSessionArray());

        // the new configuration array 'roles' and 'do_mapping' will not be saved to the session
        // and would otherwise be lost. Those must be saved to the file on disk instead.

        // the 'mapping' array is missing because it was saved to disk and not used otherwise
        // it must be restored, and THEN saved:
        $configFileName = session()->get(Constants::UPLOAD_CONFIG_FILE);
        if (null !== $configFileName) {
            $diskArray  = json_decode(StorageService::getContent($configFileName), true, JSON_THROW_ON_ERROR);
            $diskConfig = Configuration::fromArray($diskArray);
            // save the mapping from the diskConfig to our updated config:
            $configuration->setMapping($diskConfig->getMapping());
        }

        // then this is the new, full array:
        $fullArray = $configuration->toArray();

        // and it can be saved on disk:
        $configFileName = StorageService::storeArray($fullArray);
        Log::debug(sprintf('Old configuration was stored under key "%s".', session()->get(Constants::UPLOAD_CONFIG_FILE)));

        // this is a new config file name.
        session()->put(Constants::UPLOAD_CONFIG_FILE, $configFileName);

        Log::debug(sprintf('New configuration is stored under key "%s".', session()->get(Constants::UPLOAD_CONFIG_FILE)));

        // set role config as complete.
        session()->put(Constants::ROLES_COMPLETE_INDICATOR, true);

        // redirect to mapping thing.
        if (true === $needsMapping) {
            return redirect()->route('006-mapping.index');
        }
        // otherwise, store empty mapping, and continue:
        // set map config as complete.
        session()->put(Constants::READY_FOR_CONVERSION, true);
        return redirect()->route('007-convert.index');
    }

    /**
     * Will tell you if any role needs mapping.
     *
     * @param array $array
     *
     * @return bool
     */
    private function needMapping(array $array): bool
    {
        $need = false;
        foreach ($array as $value) {
            if (true === $value) {
                $need = true;
            }
        }

        return $need;
    }
}
