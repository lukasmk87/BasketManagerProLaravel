<?php

namespace App\Exceptions;

use Exception;

/**
 * VoucherException
 *
 * Custom exception for voucher validation and redemption errors.
 * Includes an error code for frontend handling.
 */
class VoucherException extends Exception
{
    protected string $errorCode;

    /**
     * Create a new VoucherException.
     *
     * @param  string  $message  Human-readable error message (German)
     * @param  string  $errorCode  Machine-readable error code for frontend
     */
    public function __construct(string $message, string $errorCode = 'unknown')
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Create exception for voucher not found.
     */
    public static function notFound(): self
    {
        return new self('Voucher-Code nicht gefunden.', 'not_found');
    }

    /**
     * Create exception for wrong tenant.
     */
    public static function wrongTenant(): self
    {
        return new self('Dieser Voucher ist nicht für deinen Bereich gültig.', 'wrong_tenant');
    }

    /**
     * Create exception for inactive voucher.
     */
    public static function inactive(): self
    {
        return new self('Dieser Voucher ist nicht mehr aktiv.', 'inactive');
    }

    /**
     * Create exception for voucher not yet valid.
     */
    public static function notYetValid(): self
    {
        return new self('Dieser Voucher ist noch nicht gültig.', 'not_yet_valid');
    }

    /**
     * Create exception for expired voucher.
     */
    public static function expired(): self
    {
        return new self('Dieser Voucher ist abgelaufen.', 'expired');
    }

    /**
     * Create exception for exhausted voucher.
     */
    public static function exhausted(): self
    {
        return new self('Dieser Voucher wurde bereits maximal eingelöst.', 'exhausted');
    }

    /**
     * Create exception for already redeemed.
     */
    public static function alreadyRedeemed(): self
    {
        return new self('Du hast diesen Voucher bereits eingelöst.', 'already_redeemed');
    }

    /**
     * Create exception for wrong plan.
     */
    public static function wrongPlan(): self
    {
        return new self('Dieser Voucher ist nicht für den gewählten Plan gültig.', 'wrong_plan');
    }
}
