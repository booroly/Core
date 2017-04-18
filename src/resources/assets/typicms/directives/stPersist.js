angular.module('smart-table').directive('stPersist', function () {
    return {
        require: '^stTable',
        link: function (scope, element, attr, ctrl) {
            var nameSpace = attr.stPersist;

            //save the table state every time it changes
            scope.$watch(function () {
                return ctrl.tableState();
            }, function (newValue, oldValue) {
                if (newValue !== oldValue) {
                    sessionStorage.setItem(nameSpace, JSON.stringify(newValue));
                }
            }, true);

            //fetch the table state when the directive is loaded
            if (sessionStorage.getItem(nameSpace)) {
                var savedState = JSON.parse(sessionStorage.getItem(nameSpace));
                var tableState = ctrl.tableState();

                angular.extend(tableState, savedState);
                ctrl.pipe();

            }

        }
    };
});
