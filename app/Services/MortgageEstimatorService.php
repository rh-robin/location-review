<?php

namespace App\Services;

class MortgageEstimatorService
{
    /**
     * Default interest rate if user does not provide one
     */
    protected float $defaultInterestRate = 5.25;

    /**
     * Main mortgage estimation method
     */
    public function estimateMortgage(array $data): array
    {
        $propertyPrice = (float) $data['property_price'];
        $deposit       = (float) $data['deposit'];
        $income        = (float) $data['annual_income'];
        $termYears     = (int) $data['term_years'];

        $interestRate = isset($data['interest_rate'])
            ? (float) $data['interest_rate']
            : $this->defaultInterestRate;

        // Loan amount
        $loanAmount = $this->calculateLoanAmount($propertyPrice, $deposit);

        // Borrowing power (client rule)
        $borrowingPower = $this->calculateBorrowingPower($income);

        // Monthly payment
        $monthlyPayment = $this->calculateMonthlyPayment(
            $loanAmount,
            $interestRate,
            $termYears
        );

        return [
            'loan_amount' => round($loanAmount),
            'borrowing_power' => round($borrowingPower),
            'estimated_monthly_payment' => round($monthlyPayment),
            'interest_rate_used' => $interestRate,
            'term_years' => $termYears
        ];
    }

    /**
     * Loan = property price − deposit
     */
    protected function calculateLoanAmount(float $price, float $deposit): float
    {
        return max($price - $deposit, 0);
    }

    /**
     * Borrowing power based on income × 4.5
     */
    protected function calculateBorrowingPower(float $income): float
    {
        return $income * 4.5;
    }

    /**
     * Standard mortgage amortization formula
     */
    protected function calculateMonthlyPayment(
        float $loan,
        float $annualRate,
        int $termYears
    ): float {

        $monthlyRate = ($annualRate / 100) / 12;
        $months = $termYears * 12;

        if ($monthlyRate == 0) {
            return $loan / $months;
        }

        return $loan * $monthlyRate /
            (1 - pow(1 + $monthlyRate, -$months));
    }



    public function estimateRemortgage(array $data): array
    {
        $propertyValue = (float) $data['property_value'];
        $loan = (float) $data['outstanding_balance'];
        $currentRate = (float) $data['current_interest_rate'];
        $newRate = (float) $data['new_interest_rate'];
        $termYears = (int) $data['remaining_term_years'];

        // Current monthly payment
        $currentPayment = $this->calculateMonthlyPayment(
            $loan,
            $currentRate,
            $termYears
        );

        // New monthly payment
        $newPayment = $this->calculateMonthlyPayment(
            $loan,
            $newRate,
            $termYears
        );

        // Monthly saving
        $monthlySaving = max($currentPayment - $newPayment, 0);

        // 5 year saving
        $saving5Years = $monthlySaving * 60;

        // Switching score
        $score = $this->calculateSwitchingScore(
            $loan,
            $currentRate,
            $newRate,
            $monthlySaving,
            $termYears,
            $saving5Years
        );

        return [
            'current_monthly_payment' => round($currentPayment),
            'new_monthly_payment' => round($newPayment),
            'monthly_saving' => round($monthlySaving),
            'saving_5_years' => round($saving5Years),
            'switching_benefit_score' => $score
        ];
    }


    protected function calculateSwitchingScore(
        float $loan,
        float $currentRate,
        float $newRate,
        float $monthlySaving,
        int $termYears,
        float $saving5Years
    ): int {

        $score = 0;

        // Interest rate improvement (40%)
        $rateDiff = max($currentRate - $newRate, 0);
        $rateScore = min(($rateDiff / 3) * 40, 40);

        // Monthly saving score (30%)
        if ($loan > 0) {
            $savingRatio = $monthlySaving / $loan;
            $savingScore = min($savingRatio * 3000, 30);
        } else {
            $savingScore = 0;
        }

        // Remaining term score (20%)
        $termScore = min(($termYears / 30) * 20, 20);

        // 5-year saving score (10%)
        if ($loan > 0) {
            $saving5Ratio = $saving5Years / $loan;
            $saving5Score = min($saving5Ratio * 10, 10);
        } else {
            $saving5Score = 0;
        }

        $score = $rateScore + $savingScore + $termScore + $saving5Score;

        return (int) max(0, min(100, round($score)));
    }
}
