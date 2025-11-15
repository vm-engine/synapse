<?php

return [
    // Page headers
    'page_title' => 'Todos',
    'page_description' => 'Manage your tasks and stay organized',

    // Form labels
    'form' => [
        'add_title' => 'Add New Todo',
        'edit_title' => 'Edit Todo',
        'title' => 'Title',
        'title_placeholder' => 'Enter todo title',
        'detail' => 'Detail',
        'detail_placeholder' => 'Enter todo details (optional)',
        'tags' => 'Tags',
        'tags_placeholder' => 'Select or create tags',
        'due_date' => 'Due Date',
    ],

    // Buttons
    'buttons' => [
        'reset' => 'Reset',
        'cancel_edit' => 'Cancel Edit',
        'save' => 'Save',
        'update' => 'Update',
        'saving' => 'Saving...',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    // Filters
    'filters' => [
        'search' => 'Search',
        'search_placeholder' => 'Search todos...',
        'sort_by' => 'Sort By',
        'status' => 'Status',
        'show_overdue' => 'Show Only Overdue',
    ],

    // Sort options
    'sort' => [
        'newest_first' => 'Newest First',
        'oldest_first' => 'Oldest First',
        'due_date' => 'Due Date',
    ],

    // Status options
    'status' => [
        'all' => 'All',
        'pending' => 'Pending',
        'completed' => 'Completed',
    ],

    // Messages
    'messages' => [
        'created' => 'Todo created successfully!',
        'updated' => 'Todo updated successfully!',
        'deleted' => 'Todo ":title" deleted successfully!',
        'status_updated' => 'Todo status updated!',
        'throttle' => 'Please wait :seconds seconds before submitting again.',
    ],

    // Notifications
    'notifications' => [
        'success' => 'Success',
        'warning' => 'Please Wait',
    ],

    // Confirm dialog
    'confirm' => [
        'delete_title' => 'Delete Todo',
        'delete_message' => 'Are you sure you want to delete ":title"? This action cannot be undone.',
        'delete_confirm' => 'Yes, Delete',
        'delete_cancel' => 'Cancel',
    ],

    // Empty states
    'empty' => [
        'no_results' => 'No todos found matching your filters.',
        'no_todos' => 'No todos yet. Create your first one!',
    ],

    // List items
    'list' => [
        'loading_more' => 'Loading more...',
        'completed_at' => 'Completed :time',
        'overdue' => 'Overdue',
        'due_today' => 'Today',
    ],

    // Tooltips
    'tooltips' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
];
