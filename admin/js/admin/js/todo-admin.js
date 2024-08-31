jQuery(document).ready(function ($) {
    // Initialize datepicker for due dates
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd' // Format of the date
    });

    // Add new to-do item
    $('#add-todo').on('click', function () {
        var newItem = $('#new-todo-item').val();
        var dueDate = $('#new-todo-due-date').val();
        var priority = $('#new-todo-priority').val();
        var category = $('#new-todo-category').val();

        if (newItem.length === 0) {
            alert('Please enter a to-do item.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'add_todo',
                title: newItem,
                due_date: dueDate,
                priority: priority,
                category: category,
            },
            success: function (response) {
                if (response.success) {
                    $('#todo-items').append('<li data-id="' + response.data.id + '">' + newItem + ' (Due: ' + dueDate + ', Priority: ' + priority + ', Category: ' + category + ') <button class="edit-todo">Edit</button> <button class="remove-todo">Remove</button></li>');
                    $('#new-todo-item, #new-todo-due-date, #new-todo-priority, #new-todo-category').val('');
                }
            }
        });
    });

    // Remove a to-do item
    $('#todo-items').on('click', '.remove-todo', function () {
        var listItem = $(this).parent();
        var id = listItem.data('id');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'remove_todo',
                id: id,
            },
            success: function (response) {
                if (response.success) {
                    listItem.remove();
                }
            }
        });
    });

    // Edit a to-do item
    $('#todo-items').on('click', '.edit-todo', function () {
        var listItem = $(this).parent();
        var id = listItem.data('id');
        var newTitle = prompt('Edit your to-do item:', listItem.text().trim());

        if (newTitle && newTitle.length > 0) {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'edit_todo',
                    id: id,
                    title: newTitle,
                },
                success: function (response) {
                    if (response.success) {
                        listItem.html(newTitle + ' <button class="edit-todo">Edit</button> <button class="remove-todo">Remove</button>');
                    }
                }
            });
        }
    });

    // Add new category dynamically from the To-Do editor
    $('#add_new_category_button').on('click', function () {
        var newCategory = $('#new_category_name').val().trim();

        if (newCategory === '') {
            alert('Please enter a category name.');
            return;
        }

        $.ajax({
            url: todoCategory.ajax_url,
            type: 'POST',
            data: {
                action: 'todo_add_new_category',
                new_category: newCategory,
                nonce: todoCategory.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Add the new category to the dropdown and select it
                    $('<option>', {
                        value: response.data,
                        text: response.data,
                        selected: true
                    }).appendTo('#todo_category');

                    // Clear the input field
                    $('#new_category_name').val('');
                    alert('Category added successfully!');
                } else {
                    alert(response.data);
                }
            },
            error: function () {
                alert('An error occurred while adding the category.');
            }
        });
    });
});
