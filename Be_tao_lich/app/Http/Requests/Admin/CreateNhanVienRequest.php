<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class CreateNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hoTen = trim((string)($this->input('ho_ten') ?? $this->input('name') ?? ''));
        $soDienThoai = trim((string)($this->input('so_dien_thoai') ?? $this->input('phone') ?? ''));
        $rawEmail = $this->input('email') ?? $this->input('account.email');
        $email = (!empty($rawEmail) && trim((string)$rawEmail) !== '') ? trim((string)$rawEmail) : null;

        $merges = [
            'ho_ten'        => $hoTen !== '' ? $hoTen : null,
            'so_dien_thoai' => $soDienThoai !== '' ? $soDienThoai : null,
        ];

        if ($this->has('birthDate') || $this->has('ngay_sinh')) {
            $rawBirth = $this->input('ngay_sinh') ?? $this->input('birthDate');
            $merges['ngay_sinh'] = (!empty($rawBirth) && trim((string)$rawBirth) !== '') ? trim((string)$rawBirth) : null;
        }

        if ($this->has('contract') || $this->has('loai_hop_dong')) {
            $merges['loai_hop_dong'] = $this->input('loai_hop_dong') ?? $this->input('contract');
        }

        if ($this->has('hourlyWage') || $this->has('muc_luong_gio')) {
            $merges['muc_luong_gio'] = $this->input('muc_luong_gio') ?? $this->input('hourlyWage');
        }

        if ($this->has('status') || $this->has('trang_thai')) {
            $merges['trang_thai'] = $this->input('trang_thai') ?? $this->input('status');
        }

        if ($this->has('role') || $this->has('roles') || $this->has('role_tag_id')) {
            $roleVal = $this->input('role');
            if (empty($roleVal) && is_array($this->input('roles')) && count($this->input('roles')) > 0) {
                $roleVal = $this->input('roles')[0];
            }
            if (!empty($roleVal)) {
                $merges['role'] = $roleVal;
            }
        }

        if ($email !== null) {
            $merges['email'] = $email;
        }

        $this->merge($merges);
    }

    public function rules(): array
    {
        return [
            'ho_ten'        => 'required|string|max:255',
            'so_dien_thoai' => 'required|string|max:20',
            'ngay_sinh'     => 'nullable',
            'loai_hop_dong' => 'nullable|string|max:50',
            'muc_luong_gio' => 'nullable|numeric|min:0',
            'role_tag_id'   => 'nullable|integer',
            'role'          => 'nullable|string',
            'trang_thai'    => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:255|unique:tai_khoans,email',
            'password'      => 'nullable|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required'        => 'Họ và tên không được để trống.',
            'so_dien_thoai.required' => 'Số điện thoại không được để trống.',
            'muc_luong_gio.numeric'  => 'Mức lương phải là chữ số.',
            'email.email'            => 'Email không đúng định dạng.',
            'email.unique'           => 'Email này đã tồn tại trong hệ thống.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
