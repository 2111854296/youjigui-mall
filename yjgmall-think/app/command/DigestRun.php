<?php

namespace app\command;

use app\common\MemberNotify;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class DigestRun extends Command
{
    protected function configure()
    {
        $this->setName('digest:run')->setDescription('发送邮件订阅并推送贸易提醒');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $result = MemberNotify::run();
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            $data = method_exists($e, 'getData') ? $e->getData() : '';
            if (is_string($data) && $data !== '') {
                $output->writeln($data);
            } elseif (is_array($data)) {
                $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE));
            }
            return 1;
        }
        $mail = $result['mail'];
        $note = ($mail['skipped'] ?? '') === 'smtp' ? ' mail=skipped-no-smtp' : ' mail='.(int) ($mail['sent'] ?? 0);
        $output->writeln('alerts='.(int) $result['alerts'].$note);

        return 0;
    }
}
