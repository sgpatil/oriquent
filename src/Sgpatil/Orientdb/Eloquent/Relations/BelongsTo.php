<?php namespace Sgpatil\Orientdb\Eloquent\Relations;

use Sgpatil\Orientdb\Eloquent\Edges\EdgeIn;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class BelongsTo extends OneRelation {

    /**
     * The edge direction for this relationship.
     *
     * @var string
     */
    protected $edgeDirection = 'in';

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints)
        {
            // Get the parent node's placeholder.
            $parentNode = $this->query->getQuery()->modelAsNode([$this->parent->getTable()]);
            // Tell the query that we only need the related model returned.
            $this->query->select($this->relation);
            // Set the parent node's placeholder as the RETURN key.
            $this->query->getQuery()->from = array($parentNode);
            // Build the MATCH ()<-[]-() Cypher clause.
            $this->query->matchIn($this->parent, $this->related, $this->relation, $this->foreignKey, $this->otherKey, $this->parent->{$this->otherKey});
            // Add WHERE clause over the parent node's matching key = value.
            $this->query->where($this->otherKey, '=', $this->parent->{$this->otherKey});
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        /**
         * We'll grab the primary key name of the related models since it could be set to
         * a non-standard name and not "id". We will then construct the constraint for
         * our eagerly loading query so it returns the proper models from execution.
         */

        // Grab the parent node placeholder
        $parentNode = $this->query->getQuery()->modelAsNode($this->parent->getTable());

        // Tell the builder to select both models of the relationship
        $this->query->select($this->relation, $parentNode);

        // Setup for their mutation so they don't breed weird stuff like... humans ?!
        $this->query->addMutation($this->relation, $this->related);
        $this->query->addMutation($parentNode, $this->parent);

        // Set the parent node's placeholder as the RETURN key.
        $this->query->getQuery()->from = array($parentNode);
        // Build the MATCH ()<-[]-() Cypher clause.
        $this->query->matchIn($this->parent, $this->related, $this->relation, $this->foreignKey, $this->otherKey, $this->parent->{$this->otherKey});
        // Add WHERE clause over the parent node's matching keys [values...].
        $this->query->whereIn($this->otherKey, $this->getEagerModelKeys($models));
    }

    /**
     * Get an instance of the EdgeIn relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array         $attributes
     * @return \Sgpatil\Orientdb\Eloquent\Edges\EdgeIn
     */
    public function getEdge(EloquentModel $model = null, $attributes = array())
    {
        $model = ( ! is_null($model)) ? $model : $this->parent->{$this->relation};

        // Indicate a unique relation since this only involves one other model.
        $unique = true;
        return new EdgeIn($this->query, $model, $this->parent,  $this->foreignKey, $attributes, $unique);
    }
    
    /**
     * Attach a model instance to the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array $properties The relationship properites
     * @return \Sgpatil\Orientdb\Eloquent\Edges\Edge[In, Out, etc.]
     */
    public function save(EloquentModel $model, array $properties = array()) {
        //$model->setAttribute($this->getPlainForeignKey(), $this->getParentKey());
        $model->save() ? $model : false;
        //create relationship
        $edge = $this->getEdge($model, $properties);
        $edge->save();
        return $edge;
    }

}
