<?php
namespace Zidisha\Loan\Calculator;


use Carbon\Carbon;
use Zidisha\Loan\Loan;

class InstallmentCalculator
{
    /**
     * @var \Zidisha\Loan\Loan
     */
    protected $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function yearlyInterestRateRatio()
    {
        if ($this->loan->isWeeklyInstallment()) {
            $totalTimeLoanInWeeks = $this->loan->getInstallmentCount() + round($this->loan->getExtraDays() / 7, 4);
            return $totalTimeLoanInWeeks / 52;
        }

        $totalTimeLoanInMonths = $this->loan->getInstallmentCount() + round($this->loan->getExtraDays() / 30, 4);
        return $totalTimeLoanInMonths / 12;
    }

    public function lenderInterest()
    {
        return $this->loan->getNativeAmount()
            ->multiply($this->yearlyInterestRateRatio() * $this->loan->getFinalInterestRate() / 100);
    }

    public function serviceFee()
    {
        return $this->loan->getNativeAmount()
            ->multiply($this->yearlyInterestRateRatio() * $this->loan->getServiceFeeRate() / 100);
    }

    public function totalInterest()
    {
        return $this->serviceFee()->add($this->lenderInterest());
    }

    public function totalAmount()
    {
        return $this->loan->getNativeAmount()->add($this->totalInterest());
    }

    public function installmentAmount()
    {
        return $this->totalAmount()->divide($this->loan->getInstallmentCount());
    }

    public function installmentGraceDate()
    {
        $date = Carbon::instance($this->loan->getDisbursedDate());

        return $date->addDays($this->loan->getExtraDays());
    }

    public function nthInstallmentDate($n = 1)
    {
        $date = $this->installmentGraceDate()->copy();

        if ($this->loan->isWeeklyInstallment()) {
            $date->addWeeks($n);
        } else {
            if ($date->day == 31) {
                $date->firstOfMonth()->addMonths($n)->lastOfMonth();
            } else {
                $date->addMonths($n);
            }
        }

        return $date;
    }
}
