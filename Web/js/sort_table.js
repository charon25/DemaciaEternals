function get_content(cell) {
    var type = cell.getAttribute('data-type');
    if (type == 'int') {
        return parseInt(cell.innerHTML);
    } else if (type == 'float') {
        return parseFloat(cell.innerHTML);
    } else if (type == 'champion') {
        return cell.innerHTML.split('>')[1].split('&nbsp;')[1].split(' ')[0];
    } else if (type == 'demacien') {
        return cell.innerHTML.split('>')[2].split('&nbsp;')[1].split(' ')[0];
    } else if (type == 'masteries') {
        return parseInt(cell.innerHTML.split('>')[1].split('&nbsp;')[1].replaceAll(' ', '').slice(0, -1))
    } else if (type == 'items') {
        return '';
    }
}

function sort_columns(table, index) {
    var body = table.querySelector('tbody');
    var rows = body.querySelectorAll('tr');
    var newRows = Array.from(rows);

    var direction = directions[table.id][index];
    
    newRows.sort(function (rowA, rowB) {
        var cellA = rowA.querySelectorAll('td')[index];
        var cellB = rowB.querySelectorAll('td')[index];

        var contentA = get_content(cellA);
        var contentB = get_content(cellB);

        if (contentA < contentB) return -direction;
        if (contentA > contentB) return direction;
        return 0;
    });

    directions[table.id][index] = -direction;

    [].forEach.call(rows, function (row) {
        body.removeChild(row);
    });

    newRows.forEach(function (row) {
        body.appendChild(row);
    });
}

var tables = document.getElementsByTagName('table');
var directions = {};
for (let table of tables) {
    var headers = table.querySelectorAll('th');
    directions[table.id] = [];
    [].forEach.call(headers, function (header, index) {
        directions[table.id][index] = (index == 1 ? 1 : -1);
        header.addEventListener('click', function() {
            sort_columns(table, index);
        })
    });
}