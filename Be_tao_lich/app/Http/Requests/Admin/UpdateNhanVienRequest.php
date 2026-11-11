<?php

namespace App\Http\Requests\Admin;

use App\Models\NhanVien;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawId = $this->route('id') ?? $this->input('nhan_vien_id') ?? $this->input('id');
        $nhanVienId = $rawId ? (int) preg_replace('/[^0-9]/', '', (string)$rawId) : null;
        $nhanVien = $nhanVienId ? NhanVien::find($nhanVienId) : null;

        $merges = [];

        // Đồng bộ id nhân viên
        if ($nhanVienId && !$this->has('nhan_vien_id')) {
            $merges['nhan_vien_id'] = $nhanVienId;
        }

        // Đồng bộ họ tên: nếu gửi name và chưa có ho_ten, dùng name.
        // Nếu cả hai không gửi và nhân viên đã tồn tại, dùng dữ liệu hiện có trong hồ sơ
        if ($this->has('name') && !$this->has('ho_ten')) {
            $merges['ho_ten'] = $this->input('name');
        } elseif (!$this->has('ho_ten') && !$this->has('name') && $nhanVien) {
            $merges['ho_ten'] = $nhanVien->ho_ten;
        }

        // Đồng bộ số điện thoại: tương tự họ tên
        if ($this->has('phone') && !$this->has('so_dien_thoai')) {
            $merges['so_dien_thoai'] = $this->input('phone');
        } elseif (!$this->has('so_dien_thoai') && !$this->has('phone') && $nhanVien) {
            $merges['so_dien_thoai'] = $nhanVien->so_dien_thoai;
        }

        // Đồng bộ ngày sinh
        if ($this->has('birthDate') && !$this->has('ngay_sinh')) {
            $merges['ngay_sinh'] = $this->input('birthDate');
        }

        // Đồng bộ hợp đồng
        if ($this->has('contract') && !$this->has('loai_hop_dong')) {
            $merges['loai_hop_dong'] = $this->input('contract');
        }

        // Đồng bộ lương giờ
        if ($this->has('hourlyWage') && !$this->has('muc_luong_gio')) {
            $merges['muc_luong_gio'] = $this->input('hourlyWage');
        }

        // Đồng bộ trạng thái
        if ($this->has('status') && !$this->has('trang_thai')) {
            $merges['trang_thai'] = $this->input('status');
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

        // Đồng bộ email và mật khẩu từ object account nếu có
        if (!$this->has('email') && $this->input('account.email')) {
            $merges['email'] = $this->input('account.email');
        }
        if (!$this->has('password') && $this->input('account.password')) {
            $merges['password'] = $this->input('account.password');
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'nhan_vien_id'  => 'required',
            'ho_ten'        => 'required|string|max:255',
            'so_dien_thoai' => 'nullable|string|max:20',
            'ngay_sinh'     => 'nullable',
            'loai_hop_dong' => 'nullable|string|max:50',
            'muc_luong_gio' => 'nullable|numeric|min:0',
            'role_tag_id'   => 'nullable|integer',
            'role'          => 'nullable|string',
            'trang_thai'    => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:255',
            'password'      => 'nullable|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'nhan_vien_id.required'  => 'Mã nhân viên không hợp lệ.',
            'ho_ten.required'        => 'Họ và tên không được để trống.',
            'so_dien_thoai.required' => 'Số điện thoại không được để trống.',
            'muc_luong_gio.required' => 'Mức lương theo giờ không được để trống.',
            'muc_luong_gio.numeric'  => 'Mức lương phải là chữ số.',
            'email.email'            => 'Email không đúng định dạng.',
            'password.min'           => 'Mật khẩu phải có ít nhất 6 ký tự.',
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
