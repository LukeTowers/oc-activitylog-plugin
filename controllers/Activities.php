<?php namespace LukeTowers\EasyAudit\Controllers;

use Lang;
use Flash;
use BackendMenu;
use BackendAuth;
use ApplicationException;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use LukeTowers\EasyAudit\Models\Activity as ActivityModel;

/**
 * Activities Back-end Controller
 */
class Activities extends Controller
{
    use \LukeTowers\EasyAudit\Traits\CanViewActivityRecord;

    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    public $listConfig = 'config_list.yaml';
    public $requiredPermissions = ['luketowers.easyaudit.*'];

    public function __construct()
    {
        // Restrict the filter options if the user doesn't have access to view all records
        if (!static::userHasAccess('luketowers.easyaudit.activities.view_all')) {
            $this->listConfig = $this->makeConfig($this->listConfig);
            $this->listConfig->filter = $this->makeConfig($this->listConfig->filter);

            foreach ($this->listConfig->filter->scopes as &$scope) {
                if (!empty($scope['modelClass']) && is_string($scope['options']) && class_exists($scope['modelClass'])) {
                    $scope['options'] = (new $scope['modelClass'])->{$scope['options']}(null, BackendAuth::getUser());
                }
            }
        }

        parent::__construct();

        $this->addJs('/plugins/luketowers/easyaudit/assets/js/activityController.js', 'LukeTowers.EasyAudit');

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('LukeTowers.EasyAudit', 'logs');
    }

    public function listExtendQuery($query)
    {
        if (!static::userHasAccess('luketowers.easyaudit.activities.*')) {
            $query->where('id', '0');
        }

        if (!static::userHasAccess('luketowers.easyaudit.activities.view_all')) {
            $query->fromSource(BackendAuth::getUser());
        }

        return $query;
    }

    public function index_onEmptyLog()
    {
        if (!static::userHasAccess('luketowers.easyaudit.manage_settings')) {
            throw new ApplicationException('You do not have permissions to do that');
        }

        ActivityModel::truncate();
        Flash::success(Lang::get('luketowers.easyaudit::lang.settings.empty_log_success'));
    }

    /**
     * Checks if a user has access to the provided permission
     *
     * @param string $permission The permission to check for access to
     * @return bool
     */
    public static function userHasAccess(string $permission)
    {
        $user = BackendAuth::getUser();
        // If the user isn't even logged in then deny access
        if (!$user) {
            return false;
        }
        return $user->hasAccess($permission);
    }

}
