<?php

namespace App\Domain\PurchaseEngine\Services;

class DataExecucaoService
{
    private const DIAS_EXECUCAO = [5, 15, 25];

    /**
     * RN-020: Compras nos dias 5, 15 e 25.
     * RN-021: Se sábado/domingo, próximo dia útil (segunda).
     * RN-022: Dias úteis = segunda a sexta.
     */
    public function ajustarParaDiaUtil(\DateTimeImmutable $data): \DateTimeImmutable
    {
        $dayOfWeek = (int) $data->format('N'); // 1=Mon, 6=Sat, 7=Sun

        if ($dayOfWeek === 6) {
            return $data->modify('+2 days');
        }

        if ($dayOfWeek === 7) {
            return $data->modify('+1 day');
        }

        return $data;
    }

    /**
     * @return \DateTimeImmutable[]
     */
    public function datasDoMes(int $ano, int $mes): array
    {
        $datas = [];

        foreach (self::DIAS_EXECUCAO as $dia) {
            $data = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $ano, $mes, $dia));
            $datas[] = $this->ajustarParaDiaUtil($data);
        }

        return $datas;
    }

    public function isDataExecucaoValida(\DateTimeImmutable $data): bool
    {
        $dia = (int) $data->format('j');
        $ano = (int) $data->format('Y');
        $mes = (int) $data->format('n');

        $datasValidas = $this->datasDoMes($ano, $mes);

        foreach ($datasValidas as $dataValida) {
            if ($data->format('Y-m-d') === $dataValida->format('Y-m-d')) {
                return true;
            }
        }

        return false;
    }
}
