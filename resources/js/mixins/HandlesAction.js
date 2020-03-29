import { Errors } from 'laravel-nova';

export default {

    props: {
        selectedResources: {
            type: [Array, String],
            default: () => 'all',
        },
        resourceName: {
            type: String,
            default: () => null
        },
        endpoint: {
            type: String,
            default: null,
        },
        queryString: {
            type: Object,
            default: () => ({
                currentSearch: '',
                pivotAction: false,
                encodedFilters: '',
                currentTrashed: '',
                viaResource: '',
                viaResourceId: '',
                viaRelationship: '',
            }),
        },
    },

    data: () => ({
        working: false,
        errors: null,
        actions: [],
        selectedAction: null,
    }),

    methods: {
        /**
         * Used to open action Modal
         */
        openActionModal() {
            this.showActionModal = true;
        },

        /**
         * Determine whether the action should redirect or open a confirmation modal
         */
        determineActionStrategy() {
            if(this.isAnAction)
            {
                // If the action should be triggered without confirmation and the fields in action is empty
                if (this.selectedAction.withoutConfirmation && this.selectedAction.fields.length === 0) {
                    this.executeAction()
                } else {
                    this.openActionModal()
                }
                return;
            }
            this.redirect()
        },

        /**
         * Initialize all of the action fields to empty strings.
         */
        initializeActionFields() {
            _(this.actions).each(action => {
                _(action.fields).each(field => {
                    field.fill = () => ''
                })
            });
            this.errors = new Errors();
            this.selectedAction = this.getSelectedAction
        },

        /**
         * Close the action confirmation modal.
         */
        closeConfirmationModal() {
            this.showActionModal = false;
            this.errors = new Errors()
        },

        /**
         * Get the actions available for the current resource.
         */
        getAction() {
            this.actions = [];
            return Nova.request()
                .get(`/nova-api/${this.resourceName}/actions`, {
                    params: {
                        viaResource: this.viaResource,
                        viaResourceId: this.viaResourceId,
                        viaRelationship: this.viaRelationship,
                        relationshipType: this.relationshipType,
                    },
                })
                .then(( { data } ) => {
                    this.actions = data.actions;

                    if(this.actions.length > 0)
                        return this.setSelectedAction();

                    return this.isAction = false;
                })
        },
        /**
         * Used to set selected action
         * @returns {boolean}
         */
        setSelectedAction(){
            this.errors = new Errors();
            this.selectedAction = this.getSelectedAction;
            if(this.selectedAction)
            {
                this.getGlobalFields();
                return this.isAction = true;
            }
        },

        redirect() {
            if(this.isARoute) {
                this.$router.push(this.card.route);
            }
            if(this.isALink) {
                window.open(this.card.link.href, this.card.link.target);
            }
        },

        /**
         * Execute the selected action.
         */
        executeAction() {
            this.working = true;

            Nova.request({
                method: 'post',
                url: this.endpoint || `/nova-api/${this.resourceName}/action`,
                params: this.actionRequestQueryString,
                data: this.actionFormData,
            })
                .then(({ data }) => {
                    this.showActionModal = false;
                    this.handleActionResponse(data);
                    this.working = false
                })
                .catch(error => {
                    this.working = false;

                    if (error.response.status === 422) {
                        this.errors = new Errors(error.response.data.errors)
                    }
                })
        },

        /**
         * Handle the action response. Typically either a message, download or a redirect.
         */
        handleActionResponse(response) {
            if (response.message) {
                this.$emit('actionExecuted');
                this.$toasted.show(response.message, { type: 'success' })
            } else if (response.deleted) {
                this.$emit('actionExecuted')
            } else if (response.danger) {
                this.$emit('actionExecuted');
                this.$toasted.show(response.danger, { type: 'error' })
            } else if (response.download) {
                let link = document.createElement('a');
                link.href = response.download;
                link.download = response.name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link)
            } else if (response.redirect) {
                window.location = response.redirect
            } else if (response.openInNewTab) {
                window.open(response.openInNewTab, '_blank')
            } else {
                this.$emit('actionExecuted');
                this.$toasted.show(this.__('The action ran successfully!'), { type: 'success' })
            }
            this.selectedAction = null;
        },
        /*
         * Used for getting global fields
         */
        getGlobalFields()
        {
            if(this.card.withGlobalFields)
                this.selectedAction.fields = this.selectedAction.fields.concat(this.selectedAction.globalFields);
        }
    },

    computed: {

        /**
         * Get the query string for an action request.
         */
        actionRequestQueryString() {
            return {
                action: this.selectedAction.uriKey,
                globalResourceAction: true,
                withGlobalFields: this.card.withGlobalFields,
                pivotAction: this.queryString.pivotAction,
                search: this.queryString.currentSearch,
                filters: this.queryString.encodedFilters,
                trashed: this.queryString.currentTrashed,
                viaResource: this.queryString.viaResource,
                viaResourceId: this.queryString.viaResourceId,
                viaRelationship: this.queryString.viaRelationship,
            }
        },

        /**
         * Gets the specified action from the resource action list
         *
         * @returns {*}
         */
        getSelectedAction(){
            return  _.find(this.actions, action => action.uriKey === this.card.action && !action.onlyOnDetail);
        },

        /**
         * Gather the action FormData for the given action.
         */
        actionFormData() {
            return _.tap(new FormData(), formData => {
                formData.append('resources', this.selectedAction);

                _.each(this.selectedAction.fields, field => {
                    field.fill(formData)
                })
            })
        }
    }
}
