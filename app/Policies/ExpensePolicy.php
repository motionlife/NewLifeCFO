<?php

namespace newlifecfo\Policies;

use newlifecfo\User;
use newlifecfo\Models\Expense;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the expense.
     *
     * @param  \newlifecfo\User  $user
     * @param  \newlifecfo\Expense  $expense
     * @return mixed
     */
    public function view(User $user, Expense $expense)
    {
        //
    }

    /**
     * Determine whether the user can create expenses.
     *
     * @param  \newlifecfo\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the expense.
     *
     * @param  \newlifecfo\User  $user
     * @param  \newlifecfo\Expense  $expense
     * @return mixed
     */
    public function update(User $user, Expense $expense)
    {
        //
    }

    /**
     * Determine whether the user can delete the expense.
     *
     * @param  \newlifecfo\User  $user
     * @param  \newlifecfo\Expense  $expense
     * @return mixed
     */
    public function delete(User $user, Expense $expense)
    {
        //
    }
}
