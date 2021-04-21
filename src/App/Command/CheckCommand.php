<?php

namespace App\Command;

use GeoIp2\Database\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCommand
 * @package App\Command
 */
class CheckCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'proxy:check';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addArgument('ip', InputArgument::REQUIRED, 'Which Ip should you check?');
    }

    private $geoLiteDBPath;

    public function __construct($geoLiteDBPath)
    {
        parent::__construct();
        $this->geoLiteDBPath = $geoLiteDBPath;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = 'Info ' . $input->getArgument('ip') . "\n";
        $proxies = [$input->getArgument('ip')];
        foreach ($this->check($proxies, 'http') as $result) {
            $content .= sprintf(
                "Speed: %f\nCity: %s\nReal IP: %s\nCheck time: %f",
                $result['speed'],
                $result['city'],
                $result['real_ip'],
                $result['check_time']
            );
        }

        $output->writeln($content);

        return Command::SUCCESS;
    }

    /**
     * @param array $proxies
     * @param string $type
     * @return array
     */
    protected function check(array $proxies, string $type)
    {
        $mc = curl_multi_init();
        for ($thread_no = 0; $thread_no < count($proxies); $thread_no++) {
            $c[$thread_no] = curl_init();
            curl_setopt($c[$thread_no], CURLOPT_URL, "http://google.com");
            curl_setopt($c[$thread_no], CURLOPT_HEADER, 0);
            curl_setopt($c[$thread_no], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c[$thread_no], CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($c[$thread_no], CURLOPT_TIMEOUT, 10);
            curl_setopt($c[$thread_no], CURLOPT_PROXY, trim($proxies [$thread_no]));
            curl_setopt($c[$thread_no], CURLOPT_PROXYTYPE, $type);
            curl_multi_add_handle($mc, $c[$thread_no]);
        }

        $success = [];
        do {
            while (($execrun = curl_multi_exec($mc, $running)) == CURLM_CALL_MULTI_PERFORM) {
            }
            if ($execrun != CURLM_OK) {
                break;
            }
            while ($done = curl_multi_info_read($mc)) {
                $info = curl_getinfo($done['handle']);
                $ip = trim($proxies [array_search($done['handle'], $c)]);
                if (301 === $info['http_code']) {
                    $success[] = [
                        'ip' => $ip,
                        'speed' => $info['speed_download'],
                        'city' => $this->getCountryCity($ip),
                        'real_ip' => $info['local_ip'] . ':' . $info['local_port'],
                        'check_time' => $info['total_time'],
                    ];
                } else {
                    // TODO: Invalid proxy IP
                }
                curl_multi_remove_handle($mc, $done ['handle']);
            }
        } while ($running);
        curl_multi_close($mc);

        return $success;
    }

    /**
     * @param string $ip
     * @return string
     */
    private function getCountryCity(string $ip): string
    {
        $ipPort = explode(':', $ip);
        $ipForCountry = reset($ipPort);

        try {
            $reader = new Reader($this->geoLiteDBPath);
            $record = $reader->city($ipForCountry);
            return $record->country->name . '/' . $record->city->name;
        } catch (\Throwable $throwable) {
            return '/';
        }
    }
}