<?php
namespace BoxUI\Module;

/**
 * BOX UI Extended — Module Registry
 * 
 * Every feature registers here with metadata.
 * Used to generate sidebar, check risk levels, and manage modules.
 * 
 * Risk levels: system|network|danger|readonly
 */
class ModuleRegistry
{
    private static $modules = [];
    private static $initialized = false;

    /**
     * Register a module.
     */
    public static function register(string $id, array $definition): void
    {
        self::$modules[$id] = array_merge([
            'id' => $id,
            'name' => $id,
            'description' => '',
            'category' => 'general',
            'risk' => 'readonly',
            'route' => '',
            'icon' => 'fas fa-circle',
            'enabled' => true,
            'version' => '1.0',
        ], $definition);
    }

    /**
     * Get all registered modules.
     */
    public static function all(): array
    {
        self::initDefaults();
        return self::$modules;
    }

    /**
     * Get modules by category.
     */
    public static function byCategory(string $category): array
    {
        self::initDefaults();
        return array_filter(self::$modules, function($m) use ($category) {
            return $m['category'] === $category;
        });
    }

    /**
     * Get sidebar groups (categories with their modules).
     */
    public static function sidebar(): array
    {
        self::initDefaults();
        $groups = [];
        foreach (self::$modules as $module) {
            $cat = $module['category'];
            if (!isset($groups[$cat])) {
                $groups[$cat] = [
                    'id' => $cat,
                    'name' => self::categoryLabel($cat),
                    'modules' => [],
                ];
            }
            if ($module['enabled']) {
                $groups[$cat]['modules'][] = $module;
            }
        }
        return $groups;
    }

    /**
     * Register all built-in modules.
     * Each feature calls register() when loaded, but these defaults
     * ensure sidebar always works even if features aren't loaded yet.
     */
    private static function initDefaults(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;

        // ── CLI/Clash ──────────────────────────────────────
        self::register('dashboard', [
            'name' => 'Dashboard Clash',
            'category' => 'clash',
            'icon' => 'fas fa-tachometer-alt',
            'route' => '/pages/monitor/index.php',
        ]);
        self::register('config_generator', [
            'name' => 'Config Generator',
            'category' => 'clash',
            'icon' => 'fas fa-cogs',
            'route' => '/pages/clash/ocgen.php',
        ]);
        self::register('box_settings', [
            'name' => 'BOX Settings',
            'category' => 'clash',
            'icon' => 'fas fa-box',
            'route' => '/pages/box/settings.php',
        ]);

        // ── Status ─────────────────────────────────────────
        self::register('system_info', [
            'name' => 'System Info',
            'category' => 'status',
            'icon' => 'fas fa-info-circle',
            'route' => '/pages/monitor/index.php',
        ]);
        self::register('cpu_monitor', [
            'name' => 'CPU Monitor',
            'category' => 'status',
            'icon' => 'fas fa-microchip',
            'route' => '/pages/monitor/cpu.php',
        ]);
        self::register('ram_monitor', [
            'name' => 'RAM Monitor',
            'category' => 'status',
            'icon' => 'fas fa-memory',
            'route' => '/pages/monitor/ram.php',
        ]);
        self::register('battery_monitor', [
            'name' => 'Battery Monitor',
            'category' => 'status',
            'icon' => 'fas fa-battery-three-quarters',
            'route' => '/pages/monitor/battery.php',
        ]);
        self::register('storage_monitor', [
            'name' => 'Storage Monitor',
            'category' => 'status',
            'icon' => 'fas fa-hdd',
            'route' => '/pages/monitor/storage.php',
        ]);

        // ── System ─────────────────────────────────────────
        self::register('power_manager', [
            'name' => 'Power Manager',
            'category' => 'system',
            'risk' => 'danger',
            'icon' => 'fas fa-power-off',
            'route' => '/pages/system/power.php',
        ]);
        self::register('logs', [
            'name' => 'Logs',
            'category' => 'system',
            'icon' => 'fas fa-clipboard-list',
            'route' => '/pages/system/logs.php',
        ]);

        // ── Box ────────────────────────────────────────────
        self::register('box_manager', [
            'name' => 'BOX Options',
            'category' => 'box',
            'risk' => 'system',
            'icon' => 'fas fa-server',
            'route' => '/pages/box/index.php',
        ]);
        self::register('box_exec', [
            'name' => 'BOX Execution',
            'category' => 'box',
            'risk' => 'system',
            'icon' => 'fas fa-play-circle',
            'route' => '/pages/box/executed.php',
        ]);
        
        // ── Network ────────────────────────────────────────
        self::register('ping_monitor', [
            'name' => 'Ping Monitor',
            'category' => 'network',
            'icon' => 'fas fa-chart-line',
            'route' => '/pages/network/ping_monitor.php',
        ]);
        self::register('network_monitor', [
            'name' => 'Ping Utility',
            'category' => 'network',
            'icon' => 'fas fa-signal',
            'route' => '/pages/network/monitor.php',
        ]);
        self::register('network_tools', [
            'name' => 'Network Tools',
            'category' => 'network',
            'risk' => 'system',
            'icon' => 'fas fa-wifi',
            'route' => '/pages/network/tools.php',
        ]);
        self::register('bandwidth_monitor', [
            'name' => 'Bandwidth Monitor',
            'category' => 'network',
            'icon' => 'fas fa-tachometer-alt',
            'route' => '/pages/network/bandwidth.php',
        ]);
        self::register('interface_manager', [
            'name' => 'Interface Manager',
            'category' => 'network',
            'icon' => 'fas fa-network-wired',
            'route' => '/pages/network/interface.php',
        ]);
        self::register('netlimiter', [
            'name' => 'NetLimiter',
            'category' => 'network',
            'risk' => 'system',
            'icon' => 'fas fa-ban',
            'route' => '/pages/network/netlimiter.php',
        ]);
        self::register('hotspot', [
            'name' => 'Hotspot',
            'category' => 'network',
            'risk' => 'system',
            'icon' => 'fas fa-wifi',
            'route' => '/pages/network/hotspot.php',
        ]);
        self::register('airplane_pilot', [
            'name' => 'Airplane Pilot',
            'category' => 'network',
            'risk' => 'system',
            'icon' => 'fas fa-plane',
            'route' => '/pages/network/airplane.php',
        ]);
        self::register('speedtest', [
            'name' => 'Speed Test',
            'category' => 'network',
            'icon' => 'fas fa-tachometer-alt',
            'route' => '/pages/network/speedtest.php',
        ]);
        self::register('dns_leak_test', [
            'name' => 'DNS Leak Test',
            'category' => 'network',
            'icon' => 'fas fa-search',
            'route' => '/pages/network/dns-leak.php',
        ]);
        self::register('ad_block_test', [
            'name' => 'AdBlock Test',
            'category' => 'network',
            'icon' => 'fas fa-ad',
            'route' => '/pages/network/ad-block.php',
        ]);

        // ── Services ──────────────────────────────────────
        self::register('adb_tools', [
            'name' => 'ADB Tools',
            'category' => 'services',
            'icon' => 'fas fa-laptop',
            'route' => '/pages/services/adb.php',
        ]);
        self::register('sms_viewer', [
            'name' => 'SMS Viewer',
            'category' => 'services',
            'icon' => 'fas fa-sms',
            'route' => '/pages/services/sms.php',
        ]);
        self::register('sidompul', [
            'name' => 'Sidompul',
            'category' => 'services',
            'icon' => 'fas fa-credit-card',
            'route' => '/pages/services/sidompul.php',
        ]);
    }

    private static function categoryLabel(string $cat): string
    {
        return match ($cat) {
            'clash' => 'Clash',
            'status' => 'Status',
            'system' => 'System',
            'box' => 'Box',
            'network' => 'Network',
            'services' => 'Services',
            default => ucfirst($cat),
        };
    }
}
