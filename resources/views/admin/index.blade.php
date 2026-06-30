@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="stat-card-label mb-1">Total Users</p>
                        <p class="stat-card-value mb-1">2,847</p>
                        <span class="stat-card-change"><i class="bi bi-arrow-up-short"></i>12.5%</span>
                    </div>
                    <div class="stat-card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="stat-card-label mb-1">Revenue</p>
                        <p class="stat-card-value mb-1">$48,290</p>
                        <span class="stat-card-change"><i class="bi bi-arrow-up-short"></i>8.2%</span>
                    </div>
                    <div class="stat-card-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="stat-card-label mb-1">Orders</p>
                        <p class="stat-card-value mb-1">1,429</p>
                        <span class="stat-card-change"><i class="bi bi-arrow-up-short"></i>4.1%</span>
                    </div>
                    <div class="stat-card-icon">
                        <i class="bi bi-cart-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="stat-card-label mb-1">Growth</p>
                        <p class="stat-card-value mb-1">23.6%</p>
                        <span class="stat-card-change"><i class="bi bi-arrow-down-short"></i>1.3%</span>
                    </div>
                    <div class="stat-card-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h2>Recent Orders</h2>
                    @include('admin.partials.action-link', [
                        'href' => '#',
                        'icon' => 'bi-arrow-right',
                        'label' => 'View all',
                        'variant' => 'muted',
                    ])
                </div>
                <div class="admin-panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#ORD-7829</strong></td>
                                    <td>Sarah Mitchell</td>
                                    <td>Pro Plan (Annual)</td>
                                    <td>$299.00</td>
                                    <td><span class="badge-status success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-7828</strong></td>
                                    <td>James Chen</td>
                                    <td>Starter Kit</td>
                                    <td>$49.00</td>
                                    <td><span class="badge-status warning">Pending</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-7827</strong></td>
                                    <td>Emily Rodriguez</td>
                                    <td>Enterprise License</td>
                                    <td>$1,200.00</td>
                                    <td><span class="badge-status success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-7826</strong></td>
                                    <td>Michael Park</td>
                                    <td>Add-on Pack</td>
                                    <td>$79.00</td>
                                    <td><span class="badge-status danger">Cancelled</span></td>
                                </tr>
                                <tr>
                                    <td><strong>#ORD-7825</strong></td>
                                    <td>Lisa Thompson</td>
                                    <td>Pro Plan (Monthly)</td>
                                    <td>$29.00</td>
                                    <td><span class="badge-status info">Processing</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h2>Recent Activity</h2>
                </div>
                <div class="admin-panel-body">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div>
                            <div class="activity-text"><strong>New order</strong> #ORD-7829 placed by Sarah Mitchell</div>
                            <div class="activity-time">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div>
                            <div class="activity-text"><strong>New user</strong> registered — james.chen@email.com</div>
                            <div class="activity-time">15 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div>
                            <div class="activity-text"><strong>Payment received</strong> $1,200.00 from Enterprise License</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="activity-text"><strong>Order cancelled</strong> #ORD-7826 by Michael Park</div>
                            <div class="activity-time">3 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div>
                            <div class="activity-text"><strong>Profile updated</strong> by Lisa Thompson</div>
                            <div class="activity-time">5 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
